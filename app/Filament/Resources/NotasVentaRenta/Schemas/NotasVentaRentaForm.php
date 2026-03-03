<?php

namespace App\Filament\Resources\NotasVentaRenta\Schemas;

use App\Models\ClienteDireccionEntrega;
use App\Models\Clientes;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class NotasVentaRentaForm
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
        $subtotalMadera = 0.0;
        $impuestosMadera = 0.0;

        if (!is_array($partidas)) {
            $set('../../subtotal', 0.0);
            $set('../../impuestos_total', 0.0);
            $set('../../total', 0.0);
            $set('../../saldo_pendiente', 0.0);
            $set('../../deposito', 0.0);
            return;
        }

        foreach ($partidas as $partida) {
            $subtotal += (float) ($partida['subtotal'] ?? 0);
            $impuestos += (float) ($partida['impuestos'] ?? 0);

            // Sumar subtotal de items de línea MADERA
            $itemId = $partida['item'] ?? null;
            if ($itemId) {
                $producto = Productos::find($itemId);
                if ($producto && trim($producto->linea) === 'MADERA') {
                    $subtotalMadera += (float) ($partida['subtotal'] ?? 0);
                    $impuestosMadera += (float) ($partida['impuestos'] ?? 0);
                }
            }
        }

        // Depósito = 50% del total con IVA de items de línea MADERA
        $deposito = round(($subtotalMadera + $impuestosMadera) * 0.50, 2);

        // Total = Subtotal Partidas + IVA Partidas + Depósito (sin IVA)
        $total = $subtotal + $impuestos + $deposito;

        $set('../../subtotal', $subtotal);
        $set('../../impuestos_total', $impuestos);
        $set('../../deposito', $deposito);
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
                                    ->where('documento_tipo', 'notas_venta_renta')
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
                        Hidden::make('moneda')
                            ->default('MXN'),
                        Hidden::make('tipo_cambio')->default(1.0),
                        Select::make('tipo_renta')
                            ->label('Tipo de Renta')
                            ->required()
                            ->default('dia')
                            ->options([
                                'dia' => 'Por Día',
                                'semana' => 'Por Semana',
                                'mes' => 'Por Mes',
                            ])
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                // Recalcular precios de todas las partidas al cambiar tipo de renta
                                $partidas = $get('partidas');
                                if (!is_array($partidas)) return;
                                $tipoRenta = $get('tipo_renta');
                                $cambiadas = false;
                                foreach ($partidas as $key => $partida) {
                                    $itemId = $partida['item'] ?? null;
                                    if ($itemId) {
                                        $producto = Productos::find($itemId);
                                        if ($producto) {
                                            $precio = match ($tipoRenta) {
                                                'semana' => (float) $producto->precio_renta_semana,
                                                'mes' => (float) $producto->precio_renta_mes,
                                                default => (float) $producto->precio_renta_dia,
                                            };
                                            $cantidad = (float) ($partida['cantidad'] ?? 1);
                                            $subtotal = $cantidad * $precio;
                                            $impuestos = $subtotal * 0.16;
                                            $partidas[$key]['valor_unitario'] = $precio;
                                            $partidas[$key]['subtotal'] = $subtotal;
                                            $partidas[$key]['impuestos'] = $impuestos;
                                            $partidas[$key]['total'] = $subtotal + $impuestos;
                                            $cambiadas = true;
                                        }
                                    }
                                }
                                if ($cambiadas) {
                                    $set('partidas', $partidas);
                                }
                            }),
                        Select::make('condicion_pago')
                            ->label('Condición de Pago')
                            ->required()
                            ->default('contado')
                            ->options([
                                'contado' => 'Contado',
                                'credito' => 'Crédito',
                            ]),
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
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                // Reset dirección de entrega cuando cambia el cliente
                                $set('direccion_entrega_id', null);
                            })->columnSpan(2),
                        Select::make('sucursal_id')
                            ->label('Sucursal')
                            ->options(fn () => Sucursal::orderBy('nombre')->pluck('nombre', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->sucursal_id)
                            ->disabled(fn () => !(auth()->user()?->isAdmin() ?? false)),
                        Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
                        Select::make('direccion_entrega_id')
                            ->label('Dirección de Entrega')
                            ->options(function (Get $get) {
                                $clienteId = $get('cliente_id');
                                if (!$clienteId) {
                                    return [];
                                }

                                $cliente = Clientes::find($clienteId);
                                if (!$cliente) {
                                    return [];
                                }

                                return $cliente->direccionesEntregaActivas()
                                    ->get()
                                    ->mapWithKeys(function ($direccion) {
                                        return [
                                            $direccion->id => $direccion->nombre_direccion . ' - ' . $direccion->direccion_completa
                                        ];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Seleccione la dirección donde se entregará el producto o cree una nueva')
                            ->visible(fn (Get $get) => $get('cliente_id') !== null)
                            ->createOptionForm([
                                TextInput::make('nombre_direccion')
                                    ->label('Nombre de la Dirección')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Oficina principal, Bodega, Obra...'),
                                TextInput::make('calle')
                                    ->label('Calle')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('numero_exterior')
                                    ->label('Número Exterior')
                                    ->required()
                                    ->maxLength(20),
                                TextInput::make('numero_interior')
                                    ->label('Número Interior')
                                    ->maxLength(20),
                                TextInput::make('colonia')
                                    ->label('Colonia')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('municipio')
                                    ->label('Municipio')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('estado')
                                    ->label('Estado')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('codigo_postal')
                                    ->label('Código Postal')
                                    ->required()
                                    ->maxLength(10),
                                TextInput::make('pais')
                                    ->label('País')
                                    ->default('México')
                                    ->maxLength(255),
                                Textarea::make('referencias')
                                    ->label('Referencias')
                                    ->rows(2)
                                    ->maxLength(500),
                                TextInput::make('contacto_nombre')
                                    ->label('Nombre de Contacto')
                                    ->maxLength(255),
                                TextInput::make('contacto_telefono')
                                    ->label('Teléfono de Contacto')
                                    ->maxLength(20),
                            ])
                            ->createOptionUsing(function (array $data, Get $get) {
                                $clienteId = $get('cliente_id');
                                if (!$clienteId) return null;

                                $direccion = ClienteDireccionEntrega::create([
                                    'cliente_id' => $clienteId,
                                    'nombre_direccion' => $data['nombre_direccion'],
                                    'calle' => $data['calle'],
                                    'numero_exterior' => $data['numero_exterior'],
                                    'numero_interior' => $data['numero_interior'] ?? null,
                                    'colonia' => $data['colonia'],
                                    'municipio' => $data['municipio'],
                                    'estado' => $data['estado'],
                                    'codigo_postal' => $data['codigo_postal'],
                                    'pais' => $data['pais'] ?? 'México',
                                    'referencias' => $data['referencias'] ?? null,
                                    'contacto_nombre' => $data['contacto_nombre'] ?? null,
                                    'contacto_telefono' => $data['contacto_telefono'] ?? null,
                                    'activa' => true,
                                ]);

                                return $direccion->id;
                            }),
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
                                //Repeater\TableColumn::make('Subtotal'),
                                //Repeater\TableColumn::make('Impuestos'),
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
                                        $tipoRenta = $get('../../tipo_renta') ?? 'dia';
                                        $precio = match ($tipoRenta) {
                                            'semana' => (float) $producto->precio_renta_semana,
                                            'mes' => (float) $producto->precio_renta_mes,
                                            default => (float) $producto->precio_renta_dia,
                                        };
                                        $set('valor_unitario', $precio);
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
                                Hidden::make('subtotal')->default(0.0),
                                Hidden::make('impuestos')->default(0.0),
                                TextInput::make('total')
                                    ->columnSpan(1)
                                    ->numeric()
                                    ->required()
                                    ->default(0.0)
                                    ->readOnly()
                                    ->extraAttributes([
                                        'style' => 'background-color: #fff59d; font-weight: bold;',
                                    ])
                                    ->extraInputAttributes([
                                        'x-on:keydown.insert.prevent' => "
                                            const repeater = \$el.closest('.fi-fo-repeater');
                                            if (repeater) {
                                                const addBtn = repeater.querySelector('.fi-fo-repeater-add-action-btn, [wire\\\\:click*=\"addItem\"], .fi-ac-btn-action');
                                                if (addBtn) { addBtn.click(); return; }
                                                const allBtns = repeater.querySelectorAll('button');
                                                if (allBtns.length) allBtns[allBtns.length - 1].click();
                                            }
                                        ",
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
                            ->label('Subtotal Partidas')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->readOnly()
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 1.5rem; text-align: right;width:17rem;',
                            ]),
                        TextInput::make('deposito')
                            ->label('Depósito (50% Madera + IVA)')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->prefix('$')
                            ->readOnly()
                            ->extraAttributes([
                                'style' => 'background-color: #e3f2fd; font-weight: bold; font-size: 1.5rem; text-align: right;width:17rem;',
                            ]),
                        TextInput::make('impuestos_total')
                            ->label('IVA Partidas')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->readOnly()
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 1.5rem; text-align: right;width:17rem;',
                            ]),
                        TextInput::make('total')
                            ->label('Total a Pagar')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->readOnly()
                            ->extraAttributes([
                                'style' => 'background-color: #c8e6c9; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                            ]),
                        Hidden::make('saldo_pendiente')->default(0.0),
                    ])
                    ->columns(4),
            ])
            ->columns(1);
    }
}
