<?php

namespace App\Filament\Resources\NotasVentaVenta\Schemas;

use App\Models\Clientes;
use App\Models\DocumentoSerie;
use App\Models\Productos;
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

class NotasVentaVentaForm
{
    private static function recalculatePartidaTotales(Get $get, Set $set): void
    {
        $cantidad = (float) $get('cantidad');
        $valorUnitario = (float) $get('valor_unitario');
        $subtotal = $cantidad * $valorUnitario;
        $impuestos = $subtotal * 0.16; // IVA 16%

        $set('subtotal', $subtotal);
        $set('impuestos', $impuestos);
        $set('total', $subtotal + $impuestos);
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
            $set('../../saldo_pendiente', 0.0);
            return;
        }

        foreach ($partidas as $partida) {
            $subtotal += (float) ($partida['subtotal'] ?? 0);
            $impuestos += (float) ($partida['impuestos'] ?? 0);
            $total += (float) ($partida['total'] ?? 0);
        }

        $set('../../subtotal', $subtotal);
        $set('../../impuestos_total', $impuestos);
        $set('../../total', $total);
        $set('../../saldo_pendiente', $total);
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
                                    ->where('documento_tipo', 'notas_venta_venta')
                                    ->orderBy('serie')
                                    ->get()
                                    ->mapWithKeys(function (DocumentoSerie $serie) {
                                        return [$serie->serie => $serie->label()];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->preload(),
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
                        Hidden::make('estatus')
                            ->required()
                            ->default('Activa'),
                        Select::make('documento_origen_id')
                            ->label('Documento origen')
                            ->relationship('documentoOrigen', 'folio')
                            ->searchable()
                            ->preload()
                            ->visible(false),
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nombre')
                            ->searchable()
                            ->preload(),
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
                                Repeater\TableColumn::make('Item'),
                                Repeater\TableColumn::make('Precio'),
                                Repeater\TableColumn::make('Subtotal'),
                                Repeater\TableColumn::make('Impuestos'),
                                Repeater\TableColumn::make('Total'),
                            ])
                            ->schema([
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
                                        $set('valor_unitario', $producto->precio_venta);
                                        self::recalculatePartidaTotales($get, $set);
                                        self::recalculateDocumentoTotales($get, $set);
                                    }),
                                Hidden::make('descripcion'),
                                TextInput::make('valor_unitario')
                                    ->columnSpan(1)
                                    ->label('Precio')
                                    ->numeric()
                                    ->required()
                                    ->default(0.0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::recalculatePartidaTotales($get, $set);
                                        self::recalculateDocumentoTotales($get, $set);
                                    }),
                                TextInput::make('subtotal')
                                    ->columnSpan(1)
                                    ->numeric()
                                    ->required()
                                    ->default(0.0)
                                    ->readOnly(),
                                TextInput::make('impuestos')
                                    ->columnSpan(1)
                                    ->numeric()
                                    ->required()
                                    ->default(0.0)
                                    ->readOnly(),
                                TextInput::make('total')
                                    ->columnSpan(1)
                                    ->numeric()
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
                Section::make('CFDI')
                    ->schema([
                        TextInput::make('uso_cfdi')
                            ->maxLength(10),
                        TextInput::make('forma_pago')
                            ->maxLength(10),
                        TextInput::make('metodo_pago')
                            ->maxLength(10),
                        TextInput::make('regimen_fiscal_receptor')
                            ->maxLength(10),
                        TextInput::make('rfc_emisor')
                            ->maxLength(13),
                        TextInput::make('rfc_receptor')
                            ->maxLength(13),
                        TextInput::make('razon_social_receptor')
                            ->maxLength(255),
                        TextInput::make('cfdi_uuid')
                            ->maxLength(36),
                        Textarea::make('cfdi_xml')
                            ->columnSpan(4)
                            ->rows(3),
                    ])
                    ->columns(4)
                    ->visible(false),
                Section::make('Totales')
                    ->schema([
                        TextInput::make('subtotal')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                            ]),
                        TextInput::make('impuestos_total')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                            ]),
                        TextInput::make('total')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                            ]),
                        Hidden::make('saldo_pendiente')->default(0.0),
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }
}
