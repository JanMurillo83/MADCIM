<?php

namespace App\Filament\Resources\Cotizaciones\Schemas;

use App\Models\Clientes;
use App\Enums\TipoNotaRenta;
use App\Services\RentaMaderaM2Service;
use App\Support\Impuestos;
use App\Models\DocumentoSerie;
use App\Models\Productos;
use App\Models\Sucursal;
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
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;

class CotizacionesForm
{
    private static function agregarProductosAutomaticos(Get $get, Set $set): void
    {
        // Buscar los productos por su clave
        $productoRenta = Productos::where('clave', 'SRENTA-M2')->first();
        $productoDeposito = Productos::where('clave', 'DEPOGARANTIA')->first();

        if (!$productoRenta || !$productoDeposito) {
            return;
        }

        $partidas = [];

        // Agregar producto SRENTA-M2
        $partidas[] = [
            'cantidad' => 1,
            'item' => $productoRenta->id,
            'descripcion' => 'RENTA DE MADERA x M2',
            'valor_unitario' => $productoRenta->precio_venta,
            'subtotal' => $productoRenta->precio_venta,
            'impuestos' => 0,
            'total' => $productoRenta->precio_venta,
        ];

        // Agregar producto DEPOGARANTIA
        $partidas[] = [
            'cantidad' => 1,
            'item' => $productoDeposito->id,
            'descripcion' => 'DEPOSITO EN GARANTIA',
            'valor_unitario' => $productoDeposito->precio_venta,
            'subtotal' => $productoDeposito->precio_venta,
            'impuestos' => 0,
            'total' => $productoDeposito->precio_venta,
        ];

        $set('../../partidas', $partidas);

        // Recalcular totales del documento
        $subtotal = array_sum(array_column($partidas, 'subtotal'));
        $impuestos = array_sum(array_column($partidas, 'impuestos'));
        $total = array_sum(array_column($partidas, 'total'));

        $set('../../subtotal', $subtotal);
        $set('../../impuestos_total', $impuestos);
        $set('../../total', $total);
    }

    private static function recalculatePartidaTotales(Get $get, Set $set): void
    {
        $cantidad = (float) $get('cantidad');
        if ($get('../../tipo_cotizacion') === 'renta') {
            $tipo = TipoNotaRenta::tryFrom($get('tipo_nota_renta') ?? 'equipo') ?? TipoNotaRenta::Equipo;
            if ($tipo->esMaderaM2()) {
                $calculo = RentaMaderaM2Service::calcular($tipo, (float) ($get('metros_m2') ?? 0));
                $productoId = RentaMaderaM2Service::productoRentaM2Id($tipo);
                $producto = Productos::find($productoId);
                $set('item', $productoId);
                $set('descripcion', ($producto?->descripcion ?? 'Renta de madera') . ' - ' . (float) ($get('metros_m2') ?? 0) . ' M2');
                $set('cantidad', 1);
                $set('valor_unitario', $calculo['precio_renta_m2']);
                $set('subtotal', $calculo['subtotal_renta']);
                $set('impuestos', $calculo['iva_renta']);
                $set('total', $calculo['total_renta']);
                $set('deposito', $calculo['deposito']);
                return;
            }
            $producto = Productos::find($get('item'));
            if (!$producto) return;
            $periodo = $get('tipo_renta') ?? 'dia';
            $duracion = max(1, (int) ($get('duracion_renta') ?? 1));
            $dias = max(1, min(30, (int) ($get('dias_renta') ?? 25)));
            $precio = $tipo->esMadera() ? (float) $producto->precio_renta_dia : (float) match ($periodo) {
                'semana' => $producto->precio_renta_semana,
                'mes' => $producto->precio_renta_mes,
                default => $producto->precio_renta_dia,
            };
            $cantidad = $tipo->esEquipo() ? 1 : $cantidad;
            $total = round($cantidad * $precio * ($tipo->esEquipo() ? $duracion : 1), 2);
            $desglose = Impuestos::desglosarIvaIncluido($total);
            $set('cantidad', $cantidad);
            $set('valor_unitario', $precio);
            $set('subtotal', $desglose['subtotal']);
            $set('impuestos', $desglose['iva']);
            $set('total', $total);
            $set('deposito', $tipo->esMadera() ? round($total * 0.5, 2) : 0);
            return;
        }

        $valorUnitario = (float) $get('valor_unitario');
        $total = round($cantidad * $valorUnitario, 2);
        $desglose = Impuestos::desglosarIvaIncluido($total);

        $set('subtotal', $desglose['subtotal']);
        $set('impuestos', $desglose['iva']);
        $set('total', $total);
        $set('deposito', 0);
    }

    private static function recalculateDocumentoTotales(Get $get, Set $set): void
    {
        $partidas = $get('../../partidas');
        $subtotal = 0.0;
        $impuestos = 0.0;
        $total = 0.0;

        if (!is_array($partidas)) {
            $set('../../subtotal', 0.0);
            $set('../../impuestos_total', 0.0);
            $set('../../total', 0.0);
            return;
        }

        foreach ($partidas as $partida) {
            $subtotal += (float) ($partida['subtotal'] ?? 0);
            $impuestos += (float) ($partida['impuestos'] ?? 0);
            $total += (float) ($partida['total'] ?? 0) + (float) ($partida['deposito'] ?? 0);
        }

        $set('../../subtotal', $subtotal);
        $set('../../impuestos_total', $impuestos);
        $set('../../total', $total);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Encabezado')
                    ->schema([
                        Select::make('serie')
                            ->required()
                            ->options(function () {
                                return DocumentoSerie::query()
                                    ->where('documento_tipo', 'cotizaciones')
                                    ->orderBy('serie')
                                    ->get()
                                    ->mapWithKeys(function (DocumentoSerie $serie) {
                                        return [$serie->serie => $serie->label()];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->preload(),
                        Select::make('tipo_cotizacion')
                            ->label('Tipo de cotización')
                            ->options(['venta' => 'Venta', 'renta' => 'Renta'])
                            ->default('venta')
                            ->required()
                            ->live(),
                        TextInput::make('folio')
                            ->maxLength(50)
                            ->readOnly()
                            ->helperText('Se asigna al guardar.'),
                        DatePicker::make('fecha_emision')
                            ->default(Carbon::now()->format('Y-m-d'))
                            ->format('Y-m-d'),
                        Select::make('moneda')
                            ->required()
                            ->default('MXN')
                            ->live(onBlur: true)
                            ->options(['MXN' => 'MXN', 'USD' => 'USD'])
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                if ($get('moneda') == 'MXN')
                                    $set('tipo_cambio', 1.0);
                            }),
                        TextInput::make('tipo_cambio')
                            ->required()
                            ->readOnly(function (Get $get) {
                                return $get('moneda') == 'MXN';
                            })
                            ->numeric()
                            ->default(1.0),
                        TextInput::make('estatus')
                            ->required()
                            ->default('Activa')
                            ->visible(false),
                        Select::make('documento_origen_id')
                            ->label('Cotizacion origen')
                            ->relationship('documentoOrigen', 'folio')
                            ->searchable()
                            ->preload()
                            ->visible(false),
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nombre')
                            ->searchable()
                            ->preload(),
                        Select::make('sucursal_id')
                            ->label('Sucursal')
                            ->options(fn () => auth()->user()?->isAdmin()
                                ? Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')
                                : Sucursal::query()->whereKey(auth()->user()?->sucursal_id)->pluck('nombre', 'id'))
                            ->default(fn () => auth()->user()?->sucursal_id)
                            ->disabled(fn () => !(auth()->user()?->isAdmin() ?? false))
                            ->dehydrated(),
                        Placeholder::make('direccion_cliente')
                            ->label('Direccion cliente')
                            ->content(function (Get $get) {
                                $clienteId = $get('cliente_id');
                                if (!$clienteId) {
                                    return 'Selecciona un cliente.';
                                }

                                $cliente = Clientes::find($clienteId);
                                if (!$cliente) {
                                    return 'Cliente no encontrado.';
                                }

                                $exteriorInterior = trim($cliente->exterior . ' ' . $cliente->interior);
                                $partes = [
                                    $cliente->calle,
                                    $exteriorInterior ?: null,
                                    $cliente->colonia,
                                    $cliente->municipio,
                                    $cliente->estado,
                                    $cliente->pais,
                                    $cliente->codigo ? 'CP ' . $cliente->codigo : null,
                                ];

                                return implode(', ', array_filter($partes));
                            }),
                        TextInput::make('dias_renta')
                            ->label('Días de renta')
                            ->numeric()
                            ->default(1)
                            ->required(fn (Get $get) => $get('tipo_cotizacion') === 'renta')
                            ->minValue(1)
                            ->visible(fn (Get $get) => $get('tipo_cotizacion') === 'renta'),
                    ])
                    ->columns(5),
                Section::make('Partidas')
                    ->schema([
                        Repeater::make('partidas')
                            ->relationship()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::recalculateDocumentoTotales($get, $set);
                            })
                            ->compact()
                            ->table([
                                Repeater\TableColumn::make('Cantidad'),
                                Repeater\TableColumn::make('Producto'),
                                Repeater\TableColumn::make('Precio'),
                                Repeater\TableColumn::make('Subtotal'),
                                Repeater\TableColumn::make('Total'),
                            ])
                            ->schema([
                                Select::make('tipo_nota_renta')
                                    ->label('Concepto de renta')
                                    ->options(TipoNotaRenta::options())
                                    ->default('equipo')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) { self::recalculatePartidaTotales($get, $set); self::recalculateDocumentoTotales($get, $set); })
                                    ->visible(fn (Get $get) => $get('../../tipo_cotizacion') === 'renta'),
                                Select::make('tipo_renta')
                                    ->label('Periodo')
                                    ->options(['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes'])
                                    ->default('dia')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) { self::recalculatePartidaTotales($get, $set); self::recalculateDocumentoTotales($get, $set); })
                                    ->visible(fn (Get $get) => $get('../../tipo_cotizacion') === 'renta' && ($get('tipo_nota_renta') ?? 'equipo') === 'equipo'),
                                TextInput::make('duracion_renta')
                                    ->label('Duración')
                                    ->numeric()->minValue(1)->default(1)->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) { self::recalculatePartidaTotales($get, $set); self::recalculateDocumentoTotales($get, $set); })
                                    ->visible(fn (Get $get) => $get('../../tipo_cotizacion') === 'renta' && ($get('tipo_nota_renta') ?? 'equipo') === 'equipo'),
                                TextInput::make('dias_renta')
                                    ->label('Días (madera)')
                                    ->numeric()->minValue(1)->maxValue(30)->default(25)->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) { self::recalculatePartidaTotales($get, $set); self::recalculateDocumentoTotales($get, $set); })
                                    ->visible(fn (Get $get) => $get('../../tipo_cotizacion') === 'renta' && ($get('tipo_nota_renta') ?? 'equipo') === 'madera_pieza'),
                                TextInput::make('metros_m2')
                                    ->label('Metros cuadrados')
                                    ->numeric()->minValue(0.01)->default(1)->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) { self::recalculatePartidaTotales($get, $set); self::recalculateDocumentoTotales($get, $set); })
                                    ->visible(fn (Get $get) => $get('../../tipo_cotizacion') === 'renta' && str_starts_with((string) $get('tipo_nota_renta'), 'madera_m2_')),
                                Hidden::make('deposito')->default(0),
                                TextInput::make('cantidad')
                                    ->columnSpan(1)
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::recalculatePartidaTotales($get, $set);
                                        self::recalculateDocumentoTotales($get, $set);
                                    }),
                                Select::make('item')
                                    ->columnSpan(2)
                                    ->required()
                                    ->searchable()
                                    ->options(function () {
                                        return Productos::select(DB::raw("CONCAT(clave,' - ',descripcion) as descripcion"), 'id')
                                            ->pluck('descripcion', 'id');
                                    })
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $itemId = $get('item');
                                        $producto = Productos::where('id', $itemId)->first();
                                        if ($producto === null) {
                                            return;
                                        }
                                        $set('descripcion', $producto->descripcion);
                                        $precio = ($get('../../tipo_cotizacion') === 'renta')
                                            ? (float) $producto->precio_renta_dia
                                            : (float) $producto->precio_venta;
                                        $set('valor_unitario', $precio);
                                        self::recalculatePartidaTotales($get, $set);
                                        self::recalculateDocumentoTotales($get, $set);
                                    }),
                                Hidden::make('descripcion'),
                                TextInput::make('valor_unitario')
                                    ->columnSpan(1)
                                    ->label('Precio')
                                    ->numeric()
                                    ->prefix('$')
                                    ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                                    ->stripCharacters(',')
                                    ->required()
                                    ->default(0.0)
                                    ->readOnly()
                                    ->dehydrated(),
                                TextInput::make('subtotal')
                                    ->columnSpan(1)
                                    ->numeric()
                                    ->prefix('$')
                                    ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                                    ->stripCharacters(',')
                                    ->required()
                                    ->default(0.0)
                                    ->readOnly(),
                                Hidden::make('impuestos')
                                    ->default(0.0),
                                TextInput::make('total')
                                    ->columnSpan(1)
                                    ->numeric()
                                    ->prefix('$')
                                    ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                                    ->stripCharacters(',')
                                    ->required()
                                    ->default(0.0)
                                    ->readOnly()
                                    ->extraAttributes([
                                        'style' => 'background-color: #fff59d; font-weight: bold;',
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
                Section::make('Entrega y observaciones')
                    ->schema([
                        Textarea::make('direccion_entrega')
                            ->label('Direccion de entrega')
                            ->rows(2)
                            ->columnSpan(2),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(2)
                            ->columnSpan(2),
                    ])
                    ->columns(4),
                Section::make('Totales')
                    ->schema([
                        TextInput::make('subtotal')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                            ->stripCharacters(',')
                            ->default(0.0)
                            ->readOnly()
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                            ]),
                        Hidden::make('impuestos_total')
                            ->default(0.0),
                        TextInput::make('total')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                            ->stripCharacters(',')
                            ->default(0.0)
                            ->readOnly()
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                            ]),
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }
}
