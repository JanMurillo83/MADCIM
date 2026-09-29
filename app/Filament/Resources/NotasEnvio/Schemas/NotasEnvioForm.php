<?php
namespace App\Filament\Resources\NotasEnvio\Schemas;
use App\Enums\TipoNotaRenta;
use App\Models\NotaVentaRentaPartidas;
use App\Models\NotasVentaRenta;
use App\Models\Productos;
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
    private static function tipoPartidaActual(Get $get): ?TipoNotaRenta
    {
        $tipo = TipoNotaRenta::tryFrom($get('tipo_nota_renta') ?? '');
        if ($tipo) {
            return $tipo;
        }

        $partidaId = $get('nota_venta_renta_partida_id');
        if ($partidaId) {
            return TipoNotaRenta::tryFrom(
                (string) NotaVentaRentaPartidas::query()->whereKey($partidaId)->value('tipo_nota_renta'),
            );
        }

        return null;
    }

    private static function esPartidaM2(Get $get): bool
    {
        return self::tipoPartidaActual($get)?->esMaderaM2() ?? false;
    }

    private static function esPartidaEquipo(Get $get): bool
    {
        return self::tipoPartidaActual($get) === TipoNotaRenta::Equipo;
    }

    private static function bloqueaProducto(Get $get): bool
    {
        return in_array(self::tipoPartidaActual($get), [
            TipoNotaRenta::Equipo,
            TipoNotaRenta::MaderaPieza,
        ], true);
    }

    private static function diasRentaDePartida($partida): int
    {
        $tipo = TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? 'equipo') ?? TipoNotaRenta::Equipo;

        if ($tipo->esMadera()) {
            return max(1, (int) ($partida->dias_renta ?? 1));
        }

        $duracion = max(1, (int) ($partida->duracion_renta ?? 1));

        return match ($partida->tipo_renta ?? 'dia') {
            'semana' => $duracion * 7,
            'mes' => $duracion * 30,
            default => $duracion,
        };
    }

    private static function fechaVencimientoDePartida($partida, $fechaEmision): string
    {
        return Carbon::parse($fechaEmision)
            ->addDays(self::diasRentaDePartida($partida))
            ->toDateString();
    }

    private static function fechaBaseNotaRenta(?int $notaId, $fallback): Carbon
    {
        $fechaEmision = $notaId
            ? NotasVentaRenta::query()->whereKey($notaId)->value('fecha_emision')
            : null;

        return Carbon::parse($fechaEmision ?: $fallback);
    }

    private static function actualizarVencimientos(Get $get, Set $set, ?string $fechaVigencia): void
    {
        if (blank($fechaVigencia)) {
            return;
        }

        $fechaBase = Carbon::parse($fechaVigencia);

        foreach ($get('partidas') ?? [] as $indice => $partida) {
            $dias = max(1, (int) ($partida['dias_renta'] ?? 1));
            $set("partidas.{$indice}.fecha_vencimiento", $fechaBase->copy()->addDays($dias)->toDateString());
        }
    }

    private static function copiarValoresPartidaAnterior(Get $get, Set $set): void
    {
        $partidas = $get('partidas');
        if (!is_array($partidas)) {
            return;
        }

        $indices = array_keys($partidas);

        foreach ($indices as $posicion => $indice) {
            $partida = $partidas[$indice];
            if ($posicion === 0 || (filled($partida['dias_renta'] ?? null) && filled($partida['fecha_vencimiento'] ?? null))) {
                continue;
            }

            $indiceAnterior = $indices[$posicion - 1];
            $anterior = $partidas[$indiceAnterior] ?? [];
            if (!filled($partida['dias_renta'] ?? null) && filled($anterior['dias_renta'] ?? null)) {
                $partidas[$indice]['dias_renta'] = $anterior['dias_renta'];
            }
            if (!filled($partida['fecha_vencimiento'] ?? null) && filled($anterior['fecha_vencimiento'] ?? null)) {
                $partidas[$indice]['fecha_vencimiento'] = $anterior['fecha_vencimiento'];
            }
        }

        $set('partidas', $partidas);
    }

    private static function objetivoM2DePartidas(NotasVentaRenta $nota): ?float
    {
        $objetivo = $nota->partidas
            ->filter(function ($partida) use ($nota): bool {
                $tipo = TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? $nota->tipo_nota_renta ?? '');
                return $tipo?->esMaderaM2() ?? false;
            })
            ->sum(fn ($partida): float => (float) ($partida->metros_m2 ?? 0));

        if ($objetivo > 0) {
            return (float) $objetivo;
        }

        return $nota->metrosM2();
    }

    private static function m2EnviadoDeNota(NotasVentaRenta $nota): float
    {
        $partidasM2 = $nota->partidas
            ->filter(fn ($partida) => TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? $nota->tipo_nota_renta ?? '')?->esMaderaM2())
            ->pluck('id');

        $partidasEnviadas = \App\Models\NotaEnvioPartida::query()
            ->whereHas('notaEnvio', fn ($query) => $query->where('nota_venta_renta_id', $nota->id))
            ->get(['producto_id', 'cantidad', 'nota_venta_renta_partida_id'])
            ->filter(function ($partida) use ($partidasM2): bool {
                $producto = Productos::find($partida->producto_id);
                return $partidasM2->isEmpty()
                    || $partidasM2->contains($partida->nota_venta_renta_partida_id)
                    || (float) ($producto?->m2_cubre ?? 0) > 0;
            });

        return $partidasEnviadas->sum(fn ($partida): float =>
            (float) $partida->cantidad * (float) (Productos::find($partida->producto_id)?->m2_cubre ?? 0)
        );
    }

    private static function m2EnviadoDePartida(NotasVentaRenta $nota, int $partidaId): float
    {
        $partidasM2 = $nota->partidas
            ->filter(fn ($partida) => TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? $nota->tipo_nota_renta ?? '')?->esMaderaM2())
            ->pluck('id');

        return \App\Models\NotaEnvioPartida::query()
            ->whereHas('notaEnvio', fn ($query) => $query->where('nota_venta_renta_id', $nota->id))
            ->get(['producto_id', 'cantidad', 'nota_venta_renta_partida_id'])
            ->filter(function ($envioPartida) use ($partidaId, $partidasM2): bool {
                if ((int) $envioPartida->nota_venta_renta_partida_id === $partidaId) {
                    return true;
                }

                return $partidasM2->count() === 1
                    && !$envioPartida->nota_venta_renta_partida_id
                    && (float) (Productos::find($envioPartida->producto_id)?->m2_cubre ?? 0) > 0;
            })
            ->sum(fn ($envioPartida): float =>
                (float) $envioPartida->cantidad * (float) (Productos::find($envioPartida->producto_id)?->m2_cubre ?? 0)
            );
    }

    private static function partidasPendientesDeNota(NotasVentaRenta $nota): array
    {
        $partidasOrigen = collect();
        foreach ($nota->partidas as $partida) {
            $tipo = TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? $nota->tipo_nota_renta ?? 'equipo');
            if ($tipo?->esMaderaM2()) {
                $objetivoM2 = (float) ($partida->metros_m2 ?? 0);
                $enviadoM2 = self::m2EnviadoDePartida($nota, (int) $partida->id);
                if ($objetivoM2 > 0 && $objetivoM2 - $enviadoM2 <= 1.0) {
                    continue;
                }

                $desglose = $nota->desgloseM2
                    ->where('nota_venta_renta_partida_id', $partida->id);
                foreach ($desglose as $fila) {
                    $partidasOrigen->push((object) [
                        'partida_id' => $partida->id,
                        'item' => is_array($fila) ? ($fila['producto_id'] ?? null) : $fila->producto_id,
                        'descripcion' => is_array($fila) ? ($fila['descripcion'] ?? null) : $fila->descripcion,
                        'cantidad' => is_array($fila) ? ($fila['cantidad'] ?? 0) : $fila->cantidad,
                        'm2_cubre' => is_array($fila) ? ($fila['m2_cubre'] ?? 0) : ($fila->m2_cubre ?? 0),
                        'tipo_nota_renta' => $partida->tipo_nota_renta ?? $nota->tipo_nota_renta,
                    ]);
                }
                continue;
            }

            $partidasOrigen->push((object) [
                'partida_id' => $partida->id,
                'item' => $partida->item,
                'descripcion' => $partida->descripcion,
                'cantidad' => $partida->cantidad,
                'm2_cubre' => null,
                    'tipo_nota_renta' => $partida->tipo_nota_renta ?? $nota->tipo_nota_renta,
            ]);
        }

        $objetivos = [];
        foreach ($partidasOrigen as $partida) {
            if (!$partida->item) {
                continue;
            }

            $productoId = (int) $partida->item;
            $clave = $partida->partida_id . ':' . $productoId;
            $objetivos[$clave] ??= [
                'partida_id' => $partida->partida_id,
                'producto_id' => $productoId,
                'descripcion' => $partida->descripcion,
                'cantidad' => 0.0,
                'm2_cubre' => (float) ($partida->m2_cubre ?? 0),
                'tipo_nota_renta' => $partida->tipo_nota_renta ?? $nota->tipo_nota_renta,
            ];
            $objetivos[$clave]['cantidad'] += (float) $partida->cantidad;
        }

        $enviados = \App\Models\NotaEnvioPartida::whereHas('notaEnvio', function ($query) use ($nota) {
            $query->where('nota_venta_renta_id', $nota->id);
        })->get(['producto_id', 'cantidad', 'nota_venta_renta_partida_id'])
            ->groupBy(fn ($partida) => $partida->nota_venta_renta_partida_id . ':' . $partida->producto_id)
            ->map(fn ($partidas) => $partidas->sum(fn ($partida) => (float) $partida->cantidad));
        $objetivosPorProducto = collect($objetivos)->countBy('producto_id');
        $enviadosLegacy = \App\Models\NotaEnvioPartida::whereHas('notaEnvio', function ($query) use ($nota) {
            $query->where('nota_venta_renta_id', $nota->id);
        })->whereNull('nota_venta_renta_partida_id')->get(['producto_id', 'cantidad'])
            ->groupBy('producto_id')
            ->map(fn ($partidas) => $partidas->sum(fn ($partida) => (float) $partida->cantidad));

        $partidasData = [];
        foreach ($objetivos as $clave => $objetivo) {
            $cantidadEnviada = (float) ($enviados->get($clave) ?? 0);
            if ($objetivosPorProducto->get($objetivo['producto_id']) === 1) {
                $cantidadEnviada += (float) ($enviadosLegacy->get($objetivo['producto_id']) ?? 0);
            }
            $pendiente = $objetivo['cantidad'] - $cantidadEnviada;
            if ($pendiente <= 0) {
                continue;
            }

            $partidasData[] = [
                'nota_venta_renta_partida_id' => $objetivo['partida_id'],
                'tipo_nota_renta' => $objetivo['tipo_nota_renta'],
                'producto_id' => $objetivo['producto_id'],
                'descripcion' => $objetivo['descripcion'],
                'cantidad' => $pendiente,
                'dias_renta' => self::diasRentaDePartida($nota->partidas->firstWhere('id', $objetivo['partida_id'])),
                'fecha_vencimiento' => self::fechaVencimientoDePartida(
                    $nota->partidas->firstWhere('id', $objetivo['partida_id']),
                    $nota->fecha_emision ?? now(),
                ),
                'observaciones' => $objetivo['descripcion'],
            ];
        }

        foreach ($nota->partidas as $partida) {
            $tipo = TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? $nota->tipo_nota_renta ?? '');
            if (!$tipo?->esMaderaM2() || $nota->desgloseM2->where('nota_venta_renta_partida_id', $partida->id)->isNotEmpty()) {
                continue;
            }

            $objetivoM2 = (float) ($partida->metros_m2 ?? 0);
            $enviadoM2 = self::m2EnviadoDePartida($nota, (int) $partida->id);

            if ($objetivoM2 - $enviadoM2 > 1.0) {
                $partidasData[] = [
                    'nota_venta_renta_partida_id' => $partida->id,
                    'tipo_nota_renta' => $partida->tipo_nota_renta ?? $nota->tipo_nota_renta,
                    'producto_id' => null,
                    'descripcion' => 'Captura manual de productos M2 (pendiente: ' . number_format($objetivoM2 - $enviadoM2, 2) . ' M2)',
                    'cantidad' => 1,
                    'dias_renta' => self::diasRentaDePartida($partida),
                    'fecha_vencimiento' => self::fechaVencimientoDePartida($partida, $nota->fecha_emision ?? now()),
                    'observaciones' => 'Seleccione el producto físico para cubrir los M2 pendientes.',
                ];
            }
        }

        return $partidasData;
    }

    private static function notasRentaOptions(): array
    {
        return NotasVentaRenta::query()
            ->whereIn('estatus', ['Activa', 'Pagada'])
            ->with(['cliente', 'partidas', 'desgloseM2'])
            ->get()
            ->filter(fn (NotasVentaRenta $nota): bool => self::partidasPendientesDeNota($nota) !== [])
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
                        Select::make('nota_venta_renta_id')
                            ->label('Nota de Venta Renta (Origen)')
                            ->options(self::notasRentaOptions())
                            ->native()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $notaId = $get('nota_venta_renta_id');
                                if (!$notaId) return;
                                $nota = NotasVentaRenta::with(['cliente', 'partidas', 'desgloseM2'])->find($notaId);
                                if (!$nota) return;
                                $set('cliente_id', $nota->cliente_id);
                                $set('direccion_entrega_id', $nota->direccion_entrega_id);
                                $set('inicio_vigencia', Carbon::parse($nota->fecha_emision)->toDateString());
                                // Las NR M2 se surten con productos físicos del desglose,
                                // no con la partida conceptual de renta.
                                $partidasData = self::partidasPendientesDeNota($nota);
                                $tieneM2 = $nota->tieneMaderaM2();
                                if ($partidasData === [] && !$tieneM2) {
                                    Notification::make()
                                        ->title('NR sin partidas pendientes')
                                    ->body('Las partidas de esta Nota de Renta ya fueron enviadas por completo.')
                                        ->warning()
                                        ->send();
                                } elseif ($tieneM2 && $nota->desgloseM2->isEmpty() && self::objetivoM2DePartidas($nota) === null) {
                                    Notification::make()
                                        ->title('M2 no identificado')
                                        ->body('Agregue los productos y cantidades manualmente; la Nota de Venta Renta no tiene M2 persistidos.')
                                        ->warning()
                                        ->send();
                                } elseif ($tieneM2 && $nota->desgloseM2->isEmpty()) {
                                    Notification::make()
                                        ->title('M2 pendientes de envío')
                                        ->body('Capture manualmente los productos a enviar. El indicador mostrará los M2 pendientes y no generará un desglose sugerido.')
                                        ->info()
                                        ->send();
                                }
                                $set('partidas', $partidasData);
                            })
                            ->columnSpan(2),
                        DatePicker::make('fecha_emision')
                            ->default(Carbon::now()->format('Y-m-d'))
                            ->format('Y-m-d'),
                        DatePicker::make('inicio_vigencia')
                            ->label('Inicio de Vigencia')
                            ->format('Y-m-d')
                            ->required()
                            ->minDate(fn (Get $get): ?string => filled($get('nota_venta_renta_id'))
                                ? self::fechaBaseNotaRenta((int) $get('nota_venta_renta_id'), now())->toDateString()
                                : null)
                            ->maxDate(fn (Get $get): ?string => filled($get('nota_venta_renta_id'))
                                ? self::fechaBaseNotaRenta((int) $get('nota_venta_renta_id'), now())->addDays(4)->toDateString()
                                : null)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                self::actualizarVencimientos($get, $set, $state);
                            })
                            ->helperText('Debe estar entre la fecha de emisión de la Nota de Venta Renta y los 4 días posteriores.'),
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
                                if (!$get('nota_venta_renta_id')) {
                                    return false;
                                }

                                return NotasVentaRenta::with('partidas')->find($get('nota_venta_renta_id'))?->partidas
                                    ->contains(fn ($partida) => TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? '')?->esMaderaM2()) ?? false;
                            })
                            ->content(function (Get $get): string {
                                $nota = NotasVentaRenta::with('partidas')->find($get('nota_venta_renta_id'));
                                $objetivo = $nota ? self::objetivoM2DePartidas($nota) : null;
                                $yaEnviado = $nota ? self::m2EnviadoDeNota($nota) : 0.0;
                                $cubierto = collect($get('partidas') ?? [])->sum(function (array $partida): float {
                                    $cantidad = (float) ($partida['cantidad'] ?? 0);
                                    $m2Cubre = (float) ($partida['m2_cubre'] ?? 0);

                                    if ($m2Cubre <= 0 && !empty($partida['producto_id'])) {
                                        $m2Cubre = (float) (Productos::find($partida['producto_id'])?->m2_cubre ?? 0);
                                    }

                                    return $cantidad * $m2Cubre;
                                });

                                if ($objetivo === null) {
                                    return 'Objetivo: no identificado | Ya enviado: ' . number_format($yaEnviado, 2) . ' M2 | En esta nota: ' . number_format($cubierto, 2) . ' M2';
                                }

                                $faltante = max(0, $objetivo - $yaEnviado - $cubierto);
                                return 'Objetivo: ' . number_format($objetivo, 2) . ' M2 | Ya enviado: '
                                    . number_format($yaEnviado, 2) . ' M2 | En esta nota: ' . number_format($cubierto, 2)
                                    . ' M2 | Pendiente: ' . number_format($faltante, 2) . ' M2';
                            })
                            ->columnSpanFull(),
                        Repeater::make('partidas')
                            ->table([
                                Repeater\TableColumn::make('Producto'),
                                Repeater\TableColumn::make('Cantidad'),
                                Repeater\TableColumn::make('Días de Renta'),
                                Repeater\TableColumn::make('Vencimiento'),
                            ])->compact()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::copiarValoresPartidaAnterior($get, $set);
                            })
                            ->schema([
                                Hidden::make('nota_venta_renta_partida_id'),
                                Hidden::make('tipo_nota_renta'),
                                Select::make('producto_id')
                                    ->label('Producto')
                                    ->required()
                                    ->options(Productos::select(DB::raw("CONCAT(clave,' - ',descripcion) as descripcion"), 'id')
                                    ->pluck('descripcion', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->disabled(fn (Get $get): bool => self::bloqueaProducto($get))
                                    ->dehydrated()
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
                                    ->readOnly(fn (Get $get): bool => self::esPartidaEquipo($get))
                                    ->live(onBlur: true)
                                    ->columnSpan(1),
                                TextInput::make('dias_renta')
                                    ->label('Días de Renta')
                                    ->numeric()
                                    ->minValue(1)
                                    ->readOnly()
                                    ->required(),
                                DatePicker::make('fecha_vencimiento')
                                    ->label('Vencimiento')
                                    ->format('Y-m-d')
                                    ->readOnly()
                                    ->required(),
                            ])
                            ->columns(6)
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
