<?php
namespace App\Filament\Resources\NotasEnvio\Schemas;
use App\Enums\TipoNotaRenta;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Productos;
use App\Services\DesgloseM2Service;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
class NotasEnvioForm
{
    private static function partidasPendientesDeNota(NotasVentaRenta $nota): array
    {
        $partidasOrigen = $nota->esMaderaM2()
            ? ($nota->desgloseM2->isNotEmpty()
                ? $nota->desgloseM2
                : collect(self::desgloseSugerido($nota)))
                ->map(function ($fila) {
                    $productoId = is_array($fila) ? ($fila['producto_id'] ?? null) : $fila->producto_id;
                    $descripcion = is_array($fila) ? ($fila['descripcion'] ?? null) : $fila->descripcion;
                    $producto = is_array($fila) ? null : $fila->producto;
                    $cantidad = is_array($fila) ? ($fila['cantidad'] ?? 0) : $fila->cantidad;
                    $m2Cubre = is_array($fila) ? ($fila['m2_cubre'] ?? 0) : ($fila->m2_cubre ?? $producto?->m2_cubre);

                    return (object) [
                        'item' => $productoId,
                        'descripcion' => $descripcion ?: $producto?->descripcion,
                        'cantidad' => $cantidad,
                        'm2_cubre' => $m2Cubre,
                    ];
                })
            : $nota->partidas
                ->map(fn ($partida) => (object) [
                    'item' => $partida->item,
                    'descripcion' => $partida->descripcion,
                    'cantidad' => $partida->cantidad,
                    'm2_cubre' => null,
                ]);

        $objetivos = [];
        foreach ($partidasOrigen as $partida) {
            if (!$partida->item) {
                continue;
            }

            $productoId = (int) $partida->item;
            $objetivos[$productoId] ??= [
                'descripcion' => $partida->descripcion,
                'cantidad' => 0.0,
                'm2_cubre' => (float) ($partida->m2_cubre ?? 0),
            ];
            $objetivos[$productoId]['cantidad'] += (float) $partida->cantidad;
        }

        $enviados = \App\Models\NotaEnvioPartida::whereHas('notaEnvio', function ($query) use ($nota) {
            $query->where('nota_venta_renta_id', $nota->id);
        })->get(['producto_id', 'cantidad'])
            ->groupBy('producto_id')
            ->map(fn ($partidas) => $partidas->sum(fn ($partida) => (float) $partida->cantidad));

        $partidasData = [];
        foreach ($objetivos as $productoId => $objetivo) {
            $pendiente = $objetivo['cantidad'] - (float) ($enviados->get($productoId) ?? 0);
            if ($pendiente <= 0) {
                continue;
            }

            $partidasData[] = [
                'producto_id' => $productoId,
                'descripcion' => $objetivo['descripcion'],
                'cantidad' => $pendiente,
                'observaciones' => $objetivo['descripcion'],
            ];
        }

        return $partidasData;
    }

    private static function desgloseSugerido(NotasVentaRenta $nota): array
    {
        $tipo = TipoNotaRenta::tryFrom($nota->tipo_nota_renta ?? '');
        $metros = $nota->metrosM2();

        if (!$tipo?->esMaderaM2() || $metros === null || $metros <= 0) {
            return [];
        }

        return DesgloseM2Service::generar($tipo, $metros);
    }

    private static function notasRentaOptions(): array
    {
        return NotasVentaRenta::query()
            ->whereIn('estatus', ['Activa', 'Pagada'])
            ->with(['cliente', 'partidas', 'desgloseM2'])
            ->get()
            ->filter(fn (NotasVentaRenta $nota) => self::partidasPendientesDeNota($nota) !== [])
            ->mapWithKeys(function (NotasVentaRenta $nota): array {
                $label = ($nota->serie ? $nota->serie . '-' : '')
                    . $nota->folio
                    . ' - '
                    . ($nota->cliente?->nombre ?? 'Sin cliente');

                return [$nota->id => $label];
            })
            ->all();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Encabezado')
                    ->schema([
                        TextInput::make('serie')
                            ->default('NE')
                            ->maxLength(10),
                        TextInput::make('folio')
                            ->maxLength(50)
                            ->readOnly()
                            ->helperText('Se asigna al guardar.'),
                        Select::make('tipo_origen')
                            ->label('Tipo de Documento Origen')
                            ->options([
                                'renta' => 'Nota de Venta Renta',
                                'venta' => 'Nota de Venta Venta',
                            ])
                            ->default('renta')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $set('nota_venta_renta_id', null);
                                $set('nota_venta_venta_id', null);
                                $set('cliente_id', null);
                                $set('direccion_entrega_id', null);
                                $set('partidas', []);
                            })
                            ->dehydrated(false),
                        Select::make('nota_venta_renta_id')
                            ->label('Nota de Venta Renta (Origen)')
                            ->options(self::notasRentaOptions())
                            ->native()
                            ->live()
                            ->visible(fn (Get $get) => ($get('tipo_origen') ?? 'renta') === 'renta')
                            ->required(fn (Get $get) => ($get('tipo_origen') ?? 'renta') === 'renta')
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $notaId = $get('nota_venta_renta_id');
                                if (!$notaId) return;
                                $nota = NotasVentaRenta::with(['cliente', 'partidas', 'desgloseM2'])->find($notaId);
                                if (!$nota) return;
                                $set('cliente_id', $nota->cliente_id);
                                $set('direccion_entrega_id', $nota->direccion_entrega_id);
                                if ($nota->dias_renta) {
                                    $set('dias_renta', $nota->dias_renta);
                                    $fechaEmision = $get('fecha_emision') ? Carbon::parse($get('fecha_emision')) : Carbon::now();
                                    $set('fecha_vencimiento', $fechaEmision->addDays($nota->dias_renta)->format('Y-m-d'));
                                }
                                // Las NR M2 se surten con productos físicos del desglose,
                                // no con la partida conceptual de renta.
                                $partidasData = self::partidasPendientesDeNota($nota);
                                if ($partidasData === [] && !($nota->esMaderaM2() && $nota->desgloseM2->isEmpty())) {
                                    Notification::make()
                                        ->title('NR sin partidas pendientes')
                                        ->body('El desglose de esta Nota de Renta ya fue enviado por completo.')
                                        ->warning()
                                        ->send();
                                } elseif ($nota->esMaderaM2() && $nota->desgloseM2->isEmpty() && $nota->metrosM2() === null) {
                                    Notification::make()
                                        ->title('M2 no identificado')
                                        ->body('Agregue los productos y cantidades manualmente; la Nota de Venta Renta no tiene M2 persistidos.')
                                        ->warning()
                                        ->send();
                                } elseif ($nota->esMaderaM2() && $nota->desgloseM2->isEmpty()) {
                                    Notification::make()
                                        ->title('Desglose sugerido generado')
                                        ->body('Puede ajustar las partidas. El totalizador debe cubrir todos los M2 de la Nota de Venta Renta.')
                                        ->info()
                                        ->send();
                                }
                                $set('partidas', $partidasData);
                            })
                            ->columnSpan(2),
                        Select::make('nota_venta_venta_id')
                            ->label('Nota de Venta Venta (Origen)')
                            ->options(function () {
                                return NotasVentaVenta::query()
                                    ->whereIn('estatus', ['Activa', 'Pagada'])
                                    ->get()
                                    ->mapWithKeys(function ($nota) {
                                        $label = ($nota->serie ? $nota->serie . '-' : '') . $nota->folio . ' - ' . ($nota->cliente?->nombre ?? 'Sin cliente');
                                        return [$nota->id => $label];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn (Get $get) => ($get('tipo_origen') ?? 'renta') === 'venta')
                            ->required(fn (Get $get) => ($get('tipo_origen') ?? 'renta') === 'venta')
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $notaId = $get('nota_venta_venta_id');
                                if (!$notaId) return;
                                $nota = NotasVentaVenta::with(['cliente', 'partidas'])->find($notaId);
                                if (!$nota) return;
                                $set('cliente_id', $nota->cliente_id);
                                // Cargar solo partidas pendientes de surtir
                                $partidasData = [];
                                foreach ($nota->partidas as $partida) {
                                    $yaEnviado = \App\Models\NotaEnvioPartida::whereHas('notaEnvio', function ($q) use ($nota) {
                                        $q->where('nota_venta_venta_id', $nota->id);
                                    })->where('producto_id', $partida->item)->sum('cantidad');
                                    $pendiente = (float)$partida->cantidad - (float)$yaEnviado;
                                    if ($pendiente > 0) {
                                        $partidasData[] = [
                                            'producto_id' => $partida->item,
                                            'descripcion' => $partida->descripcion ?? $partida->item,
                                            'cantidad' => $pendiente,
                                            'observaciones' => $partida->descripcion ?? $partida->item,
                                        ];
                                    }
                                }
                                $set('partidas', $partidasData);
                            })
                            ->columnSpan(2),
                        DatePicker::make('fecha_emision')
                            ->default(Carbon::now()->format('Y-m-d'))
                            ->format('Y-m-d'),
                        TextInput::make('dias_renta')
                            ->label('Días de Renta')
                            ->numeric()
                            ->minValue(1)
                            ->live(onBlur: true)
                            ->visible(fn (Get $get) => ($get('tipo_origen') ?? 'renta') === 'renta')
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $dias = (int) $get('dias_renta');
                                if ($dias > 0) {
                                    $fechaEmision = $get('fecha_emision') ? Carbon::parse($get('fecha_emision')) : Carbon::now();
                                    $set('fecha_vencimiento', $fechaEmision->addDays($dias)->format('Y-m-d'));
                                }
                            }),
                        DatePicker::make('fecha_vencimiento')
                            ->label('Fecha de Vencimiento')
                            ->format('Y-m-d')
                            ->visible(fn (Get $get) => ($get('tipo_origen') ?? 'renta') === 'renta'),
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nombre')
                            ->searchable()
                            ->preload(),
                        Select::make('direccion_entrega_id')
                            ->label('Dirección de Entrega')
                            ->relationship('direccionEntrega', 'nombre_direccion')
                            ->searchable()
                            ->preload(),
                        Hidden::make('estatus')->default('Pendiente'),
                        Textarea::make('observaciones')
                            ->rows(2)
                            ->columnSpan(2),
                    ])
                    ->columns(4),
                Section::make('Productos a Enviar')
                    ->description('Seleccione los productos y cantidades que se enviarán al cliente.')
                    ->schema([
                        Placeholder::make('m2_cubiertos')
                            ->label('Cobertura M2')
                            ->visible(function (Get $get): bool {
                                if (($get('tipo_origen') ?? 'renta') !== 'renta' || !$get('nota_venta_renta_id')) {
                                    return false;
                                }

                                return NotasVentaRenta::find($get('nota_venta_renta_id'))?->esMaderaM2() ?? false;
                            })
                            ->content(function (Get $get): string {
                                $nota = NotasVentaRenta::find($get('nota_venta_renta_id'));
                                $objetivo = $nota?->metrosM2();
                                $cubierto = collect($get('partidas') ?? [])->sum(function (array $partida): float {
                                    $cantidad = (float) ($partida['cantidad'] ?? 0);
                                    $m2Cubre = (float) ($partida['m2_cubre'] ?? 0);

                                    if ($m2Cubre <= 0 && !empty($partida['producto_id'])) {
                                        $m2Cubre = (float) (Productos::find($partida['producto_id'])?->m2_cubre ?? 0);
                                    }

                                    return $cantidad * $m2Cubre;
                                });

                                if ($objetivo === null) {
                                    return 'Objetivo: no identificado | Cubierto: ' . number_format($cubierto, 2) . ' M2';
                                }

                                $faltante = max(0, $objetivo - $cubierto);
                                return 'Objetivo: ' . number_format($objetivo, 2) . ' M2 | Cubierto: '
                                    . number_format($cubierto, 2) . ' M2 | Faltante: ' . number_format($faltante, 2) . ' M2';
                            })
                            ->columnSpanFull(),
                        Repeater::make('partidas')
                            ->table([
                                Repeater\TableColumn::make('Producto'),
                                Repeater\TableColumn::make('Cantidad'),
                                Repeater\TableColumn::make('Observaciones'),
                            ])->compact()
                            ->schema([
                                Select::make('producto_id')
                                    ->label('Producto')
                                    ->required()
                                    ->options(Productos::select(DB::raw("CONCAT(clave,' - ',descripcion) as descripcion"), 'id')
                                    ->pluck('descripcion', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $productoId = $get('producto_id');
                                        $producto = Productos::find($productoId);
                                        if ($producto) {
                                            $set('descripcion', $producto->descripcion);
                                        }
                                    })
                                    ->columnSpan(3),
                                Hidden::make('descripcion'),
                                TextInput::make('cantidad')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->columnSpan(1),
                                TextInput::make('observaciones')
                                    ->label('Observaciones')
                                    ->columnSpan(2),
                            ])
                            ->columns(6)
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
