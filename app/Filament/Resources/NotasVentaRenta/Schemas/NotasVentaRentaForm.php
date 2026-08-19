<?php

namespace App\Filament\Resources\NotasVentaRenta\Schemas;

use App\Enums\TipoNotaRenta;
use App\Services\DesgloseM2Service;
use App\Services\RentaMaderaM2Service;
use App\Support\Impuestos;
use App\Support\Numero;
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
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Support\RawJs;

class NotasVentaRentaForm
{
    private const PRODUCTOS_RENTA_M2_IDS = [143, 145, 146];

    private static function tipoNotaRentaActual(Get $get): ?TipoNotaRenta
    {
        return TipoNotaRenta::tryFrom($get('tipo_nota_renta') ?? '');
    }

    private static function esMaderaM2(Get $get): bool
    {
        return self::tipoNotaRentaActual($get)?->esMaderaM2() ?? false;
    }

    private static function esMadera(Get $get): bool
    {
        return self::tipoNotaRentaActual($get)?->esMadera() ?? false;
    }

    private static function esEquipo(Get $get): bool
    {
        return !self::esMadera($get);
    }
    private static function resolverPrecioBaseRenta(Productos $producto, ?string $tipoRenta, ?TipoNotaRenta $tipoNotaRenta = null): float
    {
        // Para madera (pieza o M2) el precio es fijo de 1 a 30 días, no multiplicar por duración.
        if ($tipoNotaRenta?->esMadera() === true) {
            return (float) $producto->precio_renta_dia;
        }

        return match ($tipoRenta) {
            'semana' => (float) $producto->precio_renta_semana,
            'mes' => (float) $producto->precio_renta_mes,
            default => (float) $producto->precio_renta_dia,
        };
    }

    private static function resolverDuracionRenta(Get $get): float
    {
        return max(1, (float) ($get('duracion_renta') ?? 1));
    }

    private static function recalculatePartidasByRentaConfig(Get $get, Set $set): void
    {
        $partidas = $get('partidas');
        if (!is_array($partidas)) {
            self::recalculateDocumentoTotalesFromPartidas([], $set, false);
            return;
        }

        $tipoNotaRenta = self::tipoNotaRentaActual($get);
        $tipoRenta = $get('tipo_renta') ?? 'dia';
        $duracion = self::resolverDuracionRenta($get);
        $cambiadas = false;

        foreach ($partidas as $key => $partida) {
            $itemId = $partida['item'] ?? null;
            if (!$itemId) {
                continue;
            }

            $producto = Productos::find($itemId);
            if (!$producto) {
                continue;
            }

            $precioBase = self::resolverPrecioBaseRenta($producto, $tipoRenta, $tipoNotaRenta);
            $valorUnitario = $tipoNotaRenta?->esMadera() === true
                ? $precioBase
                : round($precioBase * $duracion, 2);
            $cantidad = (float) ($partida['cantidad'] ?? 1);
            $totalConIva = round($cantidad * $valorUnitario, 2);
            $desglose = Impuestos::desglosarIvaIncluido($totalConIva);

            $partidas[$key]['valor_unitario'] = $valorUnitario;
            $partidas[$key]['subtotal'] = $desglose['subtotal'];
            $partidas[$key]['impuestos'] = $desglose['iva'];
            $partidas[$key]['total'] = $totalConIva;
            $cambiadas = true;
        }

        if ($cambiadas) {
            $set('partidas', $partidas);
        }

        self::recalculateDocumentoTotalesFromPartidas($partidas, $set, false);
    }

    private static function recalculatePartidaTotales(Get $get, Set $set): void
    {
        $cantidad = (float) $get('cantidad');
        $valorUnitario = (float) $get('valor_unitario');
        $totalConIva = round($cantidad * $valorUnitario, 2);
        $desglose = Impuestos::desglosarIvaIncluido($totalConIva);

        $set('subtotal', $desglose['subtotal']);
        $set('impuestos', $desglose['iva']);
        $set('total', $totalConIva);
    }

    private static function recalcularM2(Get $get, Set $set): void
    {
        if (!self::esMaderaM2($get)) {
            return;
        }

        $metros = (float) ($get('metros_m2') ?? 0);
        $tipoNotaRenta = self::tipoNotaRentaActual($get);
        if (!$tipoNotaRenta) {
            return;
        }

        $calculo = RentaMaderaM2Service::calcular($tipoNotaRenta, $metros);

        $set('precio_renta_m2', $calculo['precio_renta_m2']);
        $set('precio_deposito_m2', $calculo['precio_deposito_m2']);
        $set('subtotal_renta_m2', $calculo['subtotal_renta']);
        $set('iva_renta_m2', $calculo['iva_renta']);
        $set('total_renta_m2', $calculo['total_renta']);
        $set('deposito_m2', $calculo['deposito']);

        $set('subtotal', $calculo['subtotal_renta']);
        $set('impuestos_total', $calculo['iva_renta']);
        $set('deposito', $calculo['deposito']);
        $set('total', $calculo['total']);
        $set('saldo_pendiente', $calculo['total']);

        $desglose = DesgloseM2Service::generar($tipoNotaRenta, $metros, $get('desglose_m2') ?? []);
        $set('desglose_m2', $desglose);
    }

    private static function aplicarDesgloseAM2(Get $get, Set $set): void
    {
        if (!self::esMaderaM2($get)) {
            return;
        }

        $metros = (float) ($get('metros_m2') ?? 0);
        $tipoNotaRenta = self::tipoNotaRentaActual($get);
        if (!$tipoNotaRenta) {
            return;
        }

        $desglose = $get('desglose_m2') ?? [];
        $m2TotalDesglose = 0;
        foreach ($desglose as $fila) {
            $m2TotalDesglose += (float) ($fila['m2_total'] ?? 0);
        }

        // Si el desglose actual no cubre aproximadamente los M2, regenerar
        if (abs($m2TotalDesglose - $metros) > 0.01 || empty($desglose)) {
            self::recalcularM2($get, $set);
        }
    }

    private static function setDocumentoTotales(Set $set, bool $fromRepeater, float $subtotal, float $impuestos, float $deposito, float $total): void
    {
        $prefix = $fromRepeater ? '../../' : '';

        $set($prefix . 'subtotal', Numero::redondear($subtotal));
        $set($prefix . 'impuestos_total', Numero::redondear($impuestos));
        $set($prefix . 'deposito', Numero::redondear($deposito));
        $set($prefix . 'total', Numero::redondear($total));
        $set($prefix . 'saldo_pendiente', Numero::redondear($total));
    }

    private static function recalculateDocumentoTotalesFromPartidas(mixed $partidas, Set $set, bool $fromRepeater): void
    {
        $subtotal = 0.0;
        $impuestos = 0.0;
        $subtotalMadera = 0.0;
        $impuestosMadera = 0.0;

        if (!is_array($partidas)) {
            self::setDocumentoTotales($set, $fromRepeater, 0.0, 0.0, 0.0, 0.0);
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
        $total = Numero::redondear($subtotal + $impuestos + $deposito);

        self::setDocumentoTotales($set, $fromRepeater, $subtotal, $impuestos, $deposito, $total);
    }

    private static function recalculateDocumentoTotales(Get $get, Set $set): void
    {
        self::recalculateDocumentoTotalesFromPartidas($get('../../partidas'), $set, true);
    }

    private static function recalcularFilaDesgloseM2(Get $get, Set $set): void
    {
        $cantidad = (float) ($get('cantidad') ?? 0);
        $m2Cubre = (float) ($get('m2_cubre') ?? 0);
        $set('m2_total', round($cantidad * $m2Cubre, 2));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Encabezado')
                    ->schema([
                        Select::make('tipo_nota_renta')
                            ->label('Tipo de Nota de Renta')
                            ->required()
                            ->default('equipo')
                            ->options(TipoNotaRenta::options())
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $tipo = self::tipoNotaRentaActual($get);

                                if ($tipo?->esMadera()) {
                                    $set('tipo_renta', 'dia');
                                    $set('duracion_renta', 1);
                                }

                                if ($tipo?->esMaderaM2()) {
                                    $set('partidas', []);
                                    self::recalcularM2($get, $set);
                                } else {
                                    $set('metros_m2', 0);
                                    $set('desglose_m2', []);
                                    self::recalculatePartidasByRentaConfig($get, $set);
                                }
                            }),
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
                            ->visible(fn (Get $get) => self::esEquipo($get))
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::recalculatePartidasByRentaConfig($get, $set);
                            }),
                        TextInput::make('duracion_renta')
                            ->label('Duración')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Número de días, semanas o meses según el tipo de renta.')
                            ->live(onBlur: true)
                            ->visible(fn (Get $get) => self::esEquipo($get))
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::recalculatePartidasByRentaConfig($get, $set);
                            }),
                        TextInput::make('dias_solicitados')
                            ->label('Días de Renta')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(30)
                            ->helperText('Días considerados para el vencimiento. El precio de madera sigue siendo fijo.')
                            ->visible(fn (Get $get) => self::esMadera($get)),
                        TextInput::make('dias_renta')
                            ->default(1)
                            ->visible(false)
                            ->dehydrated(true),
                        Select::make('condicion_pago')
                            ->label('Condición de Pago')
                            ->required()
                            ->default('contado')
                            ->options([
                                'contado' => 'Contado',
                                'credito' => 'Crédito',
                            ]),
                        Hidden::make('fecha_vencimiento_pago'),
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
                            ->required()
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
                            ->default(fn () => Auth::user()?->sucursal_id)
                            ->disabled(fn () => Auth::user()?->role !== 'Administrador'),
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
                    ->visible(fn (Get $get) => !self::esMaderaM2($get))
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
                                    ->label('Producto')
                                    ->required()
                                    ->rules(fn (Get $get) => [
                                        function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                            $producto = Productos::find($value);

                                            if (!$producto) {
                                                return;
                                            }

                                            // Los productos conceptuales de renta M2 no requieren existencia
                                            if (in_array((int) $producto->id, self::PRODUCTOS_RENTA_M2_IDS, true)) {
                                                return;
                                            }

                                            if (self::esMaderaM2($get)) {
                                                return;
                                            }

                                            if ((float) $producto->existencia < 1) {
                                                $fail('No se puede rentar este producto porque no tiene existencia disponible.');
                                            }
                                        },
                                    ])
                                    ->searchable()
                                    ->options(function () {
                                        return Productos::query()
                                            ->orderBy('clave')
                                            ->get()
                                            ->mapWithKeys(function (Productos $producto): array {
                                                $existencia = Numero::formato($producto->existencia, 0);

                                                return [
                                                    $producto->id => $producto->clave . ' - ' . $producto->descripcion . ' | Existencia: ' . $existencia,
                                                ];
                                            })
                                            ->all();
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $itemId = $get('item');
                                        $producto = Productos::where('id', $itemId)->first();
                                        if ($producto === null) {
                                            return;
                                        }
                                        $tipoNotaRenta = self::tipoNotaRentaActual($get);
                                        $esProductoConceptualM2 = in_array((int) $producto->id, self::PRODUCTOS_RENTA_M2_IDS, true);

                                        if (!$esProductoConceptualM2 && !self::esMaderaM2($get) && (float) $producto->existencia < 1) {
                                            $set('item', null);
                                            $set('descripcion', null);
                                            $set('valor_unitario', 0.0);
                                            $set('subtotal', 0.0);
                                            $set('impuestos', 0.0);
                                            $set('total', 0.0);
                                            Notification::make()
                                                ->title('Producto sin existencia')
                                                ->body('No se puede rentar un producto con existencia menor a 1.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        $set('descripcion', $producto->descripcion);
                                        $tipoRenta = $get('../../tipo_renta') ?? 'dia';
                                        $duracion = max(1, (float) ($get('../../duracion_renta') ?? 1));
                                        $precioBase = self::resolverPrecioBaseRenta($producto, $tipoRenta, $tipoNotaRenta);
                                        $precio = $tipoNotaRenta?->esMadera() === true
                                            ? $precioBase
                                            : round($precioBase * $duracion, 2);
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
                                    ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                                    ->stripCharacters(',')
                                    ->required()
                                    ->default(0.0)
                                    ->live(onBlur: true)
                                    ->readOnly()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::recalculatePartidaTotales($get, $set);
                                        self::recalculateDocumentoTotales($get, $set);
                                    }),
                                Hidden::make('subtotal')->default(0.0),
                                Hidden::make('impuestos')->default(0.0),
                                TextInput::make('total')
                                    ->columnSpan(1)
                                    ->numeric()
                                    ->prefix('$')
                                    ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                                    ->stripCharacters(',')
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
                Section::make('Renta de Madera por Metro Cuadrado')
                    ->visible(fn (Get $get) => self::esMaderaM2($get))
                    ->schema([
                        TextInput::make('metros_m2')
                            ->label('Metros Cuadrados')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0.01)
                            ->suffix('M2')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::recalcularM2($get, $set);
                            }),
                        Hidden::make('precio_renta_m2')->default(0),
                        Hidden::make('precio_deposito_m2')->default(0),
                        Hidden::make('total_renta_m2')->default(0),
                        TextInput::make('subtotal_renta_m2')
                            ->label('Subtotal Renta')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly(),
                        TextInput::make('iva_renta_m2')
                            ->label('IVA Renta')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly(),
                        TextInput::make('deposito_m2')
                            ->label('Depósito por M2')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly(),
                        Placeholder::make('resumen_m2')
                            ->label('Resumen')
                            ->content(function (Get $get) {
                                $metros = (float) ($get('metros_m2') ?? 0);
                                $renta = (float) ($get('total_renta_m2') ?? 0);
                                $deposito = (float) ($get('deposito_m2') ?? 0);
                                $total = (float) ($get('total') ?? 0);
                                return "M2: {$metros} | Renta c/IVA: $" . number_format($renta, 2) . " | Depósito: $" . number_format($deposito, 2) . " | Total: $" . number_format($total, 2);
                            })
                            ->columnSpanFull(),
                        Repeater::make('desglose_m2')
                            ->label('Desglose sugerido de productos')
                            ->addable(true)
                            ->deletable(true)
                            ->reorderable(false)
                            ->compact()
                            ->table([
                                Repeater\TableColumn::make('Producto'),
                                Repeater\TableColumn::make('Cantidad'),
                                Repeater\TableColumn::make('M2 c/u'),
                                Repeater\TableColumn::make('M2 Total'),
                            ])
                            ->schema([
                                Select::make('producto_id')
                                    ->label('Producto')
                                    ->required()
                                    ->searchable()
                                    ->options(function () {
                                        return Productos::query()
                                            ->where('linea', 'MADERA')
                                            ->whereNotNull('m2_cubre')
                                            ->where('m2_cubre', '>', 0)
                                            ->orderBy('clave')
                                            ->get()
                                            ->mapWithKeys(function (Productos $producto) {
                                                return [
                                                    $producto->id => $producto->clave . ' - ' . $producto->descripcion
                                                        . ' | M2: ' . $producto->m2_cubre
                                                        . ' | Existencia: ' . Numero::formato($producto->existencia, 2),
                                                ];
                                            })
                                            ->all();
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $producto = Productos::find($get('producto_id'));
                                        if ($producto) {
                                            $set('clave', $producto->clave);
                                            $set('descripcion', $producto->descripcion);
                                            $set('m2_cubre', (float) $producto->m2_cubre);
                                            self::recalcularFilaDesgloseM2($get, $set);
                                        }
                                    })
                                    ->columnSpan(3),
                                Hidden::make('clave'),
                                Hidden::make('descripcion'),
                                TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::recalcularFilaDesgloseM2($get, $set);
                                    })
                                    ->columnSpan(1),
                                TextInput::make('m2_cubre')
                                    ->label('M2 c/u')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->readOnly()
                                    ->columnSpan(1),
                                TextInput::make('m2_total')
                                    ->label('M2 Total')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->readOnly()
                                    ->columnSpan(1),
                                TextInput::make('observaciones')
                                    ->label('Observaciones')
                                    ->maxLength(255)
                                    ->columnSpan(2),
                            ])
                            ->columns(8)
                            ->columnSpanFull(),
                        Actions::make([
                            Action::make('regenerar_desglose')
                                ->label('Regenerar desglose')
                                ->icon('heroicon-o-arrow-path')
                                ->color('info')
                                ->action(function (Get $get, Set $set) {
                                    self::recalcularM2($get, $set);
                                }),
                        ]),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
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
                            ->prefix('$')
                            ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                            ->stripCharacters(',')
                            ->default(0.0)
                            ->readOnly()
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 1.5rem; text-align: right;width:17rem;',
                            ]),
                        TextInput::make('deposito')
                            ->label('Depósito (50% Madera)')
                            ->required()
                            ->numeric()
                            ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                            ->stripCharacters(',')
                            ->default(0.0)
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $subtotal = (float) $get('subtotal');
                                $impuestos = (float) $get('impuestos_total');
                                $deposito = (float) $get('deposito');
                                $total = round($subtotal + $impuestos + $deposito, 2);
                                $set('total', $total);
                                $set('saldo_pendiente', $total);
                            })
                            ->extraAttributes([
                                'style' => 'background-color: #e3f2fd; font-weight: bold; font-size: 1.5rem; text-align: right;width:17rem;',
                            ]),
                        TextInput::make('impuestos_total')
                            ->label('IVA')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                            ->stripCharacters(',')
                            ->default(0.0)
                            ->readOnly()
                            ->extraAttributes([
                                'style' => 'background-color: #f3f4f6; font-weight: bold; font-size: 1.5rem; text-align: right;width:17rem;',
                            ]),
                        TextInput::make('total')
                            ->label('Total a Pagar')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                            ->stripCharacters(',')
                            ->default(0.0)
                            ->readOnly()
                            ->extraAttributes([
                                'style' => 'background-color: #c8e6c9; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                            ]),
                        Hidden::make('saldo_pendiente')->default(0.0),
                    ])
                    ->columns(4),
                Actions::make([
                    Action::make('cancelar_captura_abajo')
                        ->label('Cancelar captura')
                        ->icon('fas-ban')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancelar captura')
                        ->modalDescription('¿Deseas cancelar la captura y regresar al listado? Se perderán los datos no guardados.')
                        ->modalSubmitActionLabel('Sí, cancelar')
                        ->action(fn ($livewire) => $livewire->cancelarCaptura()),
                    Action::make('guardar_abajo')
                        ->label('Guardar')
                        ->icon('fas-save')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar periodo de renta')
                        ->modalDescription(fn ($livewire) => $livewire->buildRentaPeriodoDescription())
                        ->modalSubmitActionLabel('Guardar')
                        ->modalCancelActionLabel('Revisar')
                        ->action(fn ($livewire) => $livewire->guardarCaptura()),
                ])
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }
}
