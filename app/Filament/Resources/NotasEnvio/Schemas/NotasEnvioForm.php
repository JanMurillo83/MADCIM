<?php
namespace App\Filament\Resources\NotasEnvio\Schemas;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\NotaVentaRentaPartidas;
use App\Models\Productos;
use App\Models\RegistroRenta;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
class NotasEnvioForm
{
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
                            ->options(function () {
                                return NotasVentaRenta::query()
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
                            ->visible(fn (Get $get) => ($get('tipo_origen') ?? 'renta') === 'renta')
                            ->required(fn (Get $get) => ($get('tipo_origen') ?? 'renta') === 'renta')
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $notaId = $get('nota_venta_renta_id');
                                if (!$notaId) return;
                                $nota = NotasVentaRenta::with(['cliente', 'partidas'])->find($notaId);
                                if (!$nota) return;
                                $set('cliente_id', $nota->cliente_id);
                                $set('direccion_entrega_id', $nota->direccion_entrega_id);
                                if ($nota->dias_renta) {
                                    $set('dias_renta', $nota->dias_renta);
                                    $fechaEmision = $get('fecha_emision') ? Carbon::parse($get('fecha_emision')) : Carbon::now();
                                    $set('fecha_vencimiento', $fechaEmision->addDays($nota->dias_renta)->format('Y-m-d'));
                                }
                                // Cargar solo partidas pendientes de surtir
                                $partidasData = [];
                                foreach ($nota->partidas as $partida) {
                                    $yaEnviado = \App\Models\NotaEnvioPartida::whereHas('notaEnvio', function ($q) use ($nota) {
                                        $q->where('nota_venta_renta_id', $nota->id);
                                    })->where('producto_id', $partida->item)->sum('cantidad');
                                    $pendiente = (float)$partida->cantidad - (float)$yaEnviado;
                                    if ($pendiente > 0) {
                                        $partidasData[] = [
                                            'producto_id' => $partida->item,
                                            'descripcion' => $partida->descripcion,
                                            'cantidad' => $pendiente,
                                            'observaciones' => $partida->descripcion,
                                        ];
                                    }
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
                Section::make('Items a Enviar')
                    ->description('Seleccione los productos y cantidades que se enviarán al cliente.')
                    ->schema([
                        Repeater::make('partidas')
                            ->relationship()
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
