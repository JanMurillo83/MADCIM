<?php

namespace App\Filament\Resources\FacturasCfdi\Schemas;

use App\Models\Clientes;
use App\Models\Configuracion;
use App\Models\DocumentoSerie;
use App\Models\Productos;
use App\Models\Sucursal;
use App\Services\FacturaCfdiDatosFiscales;
use App\Services\FacturaCfdiNotasService;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FacturasCfdiForm
{
    /**
     * @return array<string, string>
     */
    private static function opcionesCatalogoSat(string $tabla): array
    {
        return DB::table($tabla)
            ->orderBy('clave')
            ->get(['clave', 'descripcion'])
            ->mapWithKeys(static fn (object $registro): array => [
                $registro->clave => "{$registro->clave} - {$registro->descripcion}",
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function opcionesUsoCfdi(string $regimenFiscalReceptor): array
    {
        $query = DB::table('sat_uso_cfdi');

        if ($regimenFiscalReceptor === '616') {
            $query->where('clave', 'S01');
        }

        return $query
            ->orderBy('clave')
            ->get(['clave', 'descripcion'])
            ->mapWithKeys(static fn (object $registro): array => [
                $registro->clave => "{$registro->clave} - {$registro->descripcion}",
            ])
            ->all();
    }

    private static function recalculatePartidaTotales(Get $get, Set $set): void
    {
        $cantidad = (float) $get('cantidad');
        $valorUnitario = (float) $get('valor_unitario');
        $subtotal = $cantidad * $valorUnitario;
        $producto = filled($get('item')) ? Productos::query()->find($get('item')) : null;
        $impuesto = (string) ($producto?->impuesto ?: '002');
        $tipoFactor = ucfirst(strtolower((string) ($producto?->tipo_factor ?: 'Tasa')));
        $tasaConfigurada = $producto?->tasa_o_cuota;
        $tasa = is_numeric($tasaConfigurada) && (float) $tasaConfigurada >= 0
            ? (float) $tasaConfigurada
            : ($impuesto === '002' && $tipoFactor === 'Tasa' ? 0.16 : 0.0);
        $impuestos = $tipoFactor === 'Exento' ? 0.0 : $subtotal * $tasa;

        $set('subtotal', $subtotal);
        $set('impuestos', $impuestos);
        $set('total', $subtotal + $impuestos);
        $set('objeto_imp', $producto?->objeto_imp ?: ($impuestos > 0 ? '02' : '01'));
    }

    private static function recalculateDocumentoTotales(Get $get, Set $set): void
    {
        $partidas = $get('../../partidas');
        $subtotal = 0.0;
        $impuestos = 0.0;
        $total = 0.0;

        if (! is_array($partidas)) {
            $set('../../subtotal', 0.0);
            $set('../../impuestos_total', 0.0);
            $set('../../total', 0.0);

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
    }

    private static function recalculateFormTotales(array $partidas, Set $set): void
    {
        $subtotal = 0.0;
        $impuestos = 0.0;
        $total = 0.0;

        foreach ($partidas as $partida) {
            $subtotal += (float) ($partida['subtotal'] ?? 0);
            $impuestos += (float) ($partida['impuestos'] ?? 0);
            $total += (float) ($partida['total'] ?? 0);
        }

        $set('subtotal', $subtotal);
        $set('impuestos_total', $impuestos);
        $set('total', $total);
    }

    private static function actualizarPartidasDesdeNotas(Get $get, Set $set): void
    {
        $partidasNotas = app(FacturaCfdiNotasService::class)->construirPartidas(
            (array) $get('notas_venta_ids'),
            (array) $get('notas_renta_ids'),
            filled($get('cliente_id')) ? (int) $get('cliente_id') : null,
            filled($get('sucursal_id')) ? (int) $get('sucursal_id') : null,
        );

        $partidasManuales = collect((array) $get('partidas'))
            ->filter(static function (mixed $partida): bool {
                if (! is_array($partida)) {
                    return false;
                }

                $identificador = (string) ($partida['no_identificacion'] ?? '');
                $tieneProducto = filled($partida['item'] ?? null);

                return ! str_starts_with($identificador, 'NVV:')
                    && ! str_starts_with($identificador, 'NVR:')
                    && $tieneProducto;
            })
            ->values()
            ->all();

        $partidas = [...$partidasNotas, ...$partidasManuales];
        $set('partidas', $partidas);
        self::recalculateFormTotales($partidas, $set);
    }

    private static function limpiarNotasYPartidas(Get $get, Set $set): void
    {
        if ($get('notas_venta_ids') === null && $get('notas_renta_ids') === null) {
            return;
        }

        $set('notas_venta_ids', []);
        $set('notas_renta_ids', []);
        $set('partidas', []);
        $set('subtotal', 0);
        $set('impuestos_total', 0);
        $set('total', 0);
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
                                    ->where('documento_tipo', 'facturas_cfdi')
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
                                if ($get('moneda') == 'MXN') {
                                    $set('tipo_cambio', 1.0);
                                }
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
                            ->default('Borrador')
                            ->visible(false),
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
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                self::limpiarNotasYPartidas($get, $set);
                                $cliente = filled($state) ? Clientes::find($state) : null;
                                $datosFiscales = app(FacturaCfdiDatosFiscales::class)
                                    ->datosReceptor($cliente);

                                foreach ($datosFiscales as $campo => $valor) {
                                    $set($campo, $valor);
                                }

                                $set(
                                    'uso_cfdi',
                                    ($datosFiscales['regimen_fiscal_receptor'] ?? null) === '616' ? 'S01' : 'G01',
                                );
                            }),
                        Select::make('sucursal_id')
                            ->label('Sucursal')
                            ->required()
                            ->options(fn () => auth()->user()?->isAdmin()
                                ? Sucursal::orderBy('nombre')->pluck('nombre', 'id')
                                : Sucursal::query()->whereKey(auth()->user()?->sucursal_id)->pluck('nombre', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->sucursal_id)
                            ->disabled(fn () => ! (auth()->user()?->isAdmin() ?? false))
                            ->dehydrated()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::limpiarNotasYPartidas($get, $set)),
                        Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
                        Placeholder::make('direccion_cliente')
                            ->label('Direccion cliente')
                            ->content(function (Get $get) {
                                $clienteId = $get('cliente_id');
                                if (! $clienteId) {
                                    return 'Selecciona un cliente.';
                                }

                                $cliente = Clientes::find($clienteId);
                                if (! $cliente) {
                                    return 'Cliente no encontrado.';
                                }

                                $exteriorInterior = trim($cliente->exterior.' '.$cliente->interior);
                                $partes = [
                                    $cliente->calle,
                                    $exteriorInterior ?: null,
                                    $cliente->colonia,
                                    $cliente->municipio,
                                    $cliente->estado,
                                    $cliente->pais,
                                    $cliente->codigo ? 'CP '.$cliente->codigo : null,
                                ];

                                return implode(', ', array_filter($partes));
                            }),
                    ])
                    ->columns(5),
                Section::make('Notas')
                    ->description('Selecciona notas disponibles del mismo cliente y sucursal para cargar sus partidas.')
                    ->schema([
                        Select::make('notas_venta_ids')
                            ->label('Notas de venta')
                            ->multiple()
                            ->default([])
                            ->searchable()
                            ->options(fn (Get $get): array => app(FacturaCfdiNotasService::class)
                                ->opcionesNotasVenta(
                                    filled($get('cliente_id')) ? (int) $get('cliente_id') : null,
                                    filled($get('sucursal_id')) ? (int) $get('sucursal_id') : null,
                                ))
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::actualizarPartidasDesdeNotas($get, $set)),
                        Select::make('notas_renta_ids')
                            ->label('Notas de renta')
                            ->multiple()
                            ->default([])
                            ->searchable()
                            ->options(fn (Get $get): array => app(FacturaCfdiNotasService::class)
                                ->opcionesNotasRenta(
                                    filled($get('cliente_id')) ? (int) $get('cliente_id') : null,
                                    filled($get('sucursal_id')) ? (int) $get('sucursal_id') : null,
                                ))
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::actualizarPartidasDesdeNotas($get, $set)),
                    ])
                    ->columns(2)
                    ->visibleOn('create'),
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
                                        $set('clave_prod_serv', $producto->clave_prod_serv);
                                        $set('clave_unidad', $producto->clave_unidad);
                                        $set('unidad', $producto->unidad_sat);
                                        $set('objeto_imp', $producto->objeto_imp ?? ((float) $producto->impuesto > 0 ? '02' : '01'));
                                        $set('no_identificacion', null);
                                        $set('descuento', 0);
                                        $set('valor_unitario', $producto->precio_venta);
                                        self::recalculatePartidaTotales($get, $set);
                                        self::recalculateDocumentoTotales($get, $set);
                                    }),
                                Hidden::make('descripcion'),
                                Hidden::make('clave_prod_serv'),
                                Hidden::make('no_identificacion'),
                                Hidden::make('clave_unidad'),
                                Hidden::make('unidad'),
                                Hidden::make('objeto_imp'),
                                Hidden::make('descuento')->default(0),
                                TextInput::make('valor_unitario')
                                    ->columnSpan(1)
                                    ->label('Precio')
                                    ->numeric()
                                    ->prefix('$')
                                    ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                                    ->stripCharacters(',')
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
                                    ->prefix('$')
                                    ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                                    ->stripCharacters(',')
                                    ->required()
                                    ->default(0.0)
                                    ->readOnly(),
                                TextInput::make('impuestos')
                                    ->columnSpan(1)
                                    ->numeric()
                                    ->prefix('$')
                                    ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                                    ->stripCharacters(',')
                                    ->required()
                                    ->default(0.0)
                                    ->readOnly(),
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
                Section::make('CFDI')
                    ->schema([
                        Select::make('uso_cfdi')
                            ->label('Uso CFDI')
                            ->required()
                            ->searchable()
                            ->options(fn (Get $get): array => self::opcionesUsoCfdi(
                                (string) $get('regimen_fiscal_receptor'),
                            ))
                            ->default(fn (Get $get): string => $get('regimen_fiscal_receptor') === '616'
                                ? 'S01'
                                : 'G01'),
                        Select::make('forma_pago')
                            ->label('Forma de Pago')
                            ->required()
                            ->searchable()
                            ->options(fn (): array => self::opcionesCatalogoSat('sat_forma_pago'))
                            ->default('01'),
                        Select::make('metodo_pago')
                            ->label('Método de Pago')
                            ->required()
                            ->searchable()
                            ->options(fn (): array => self::opcionesCatalogoSat('sat_metodo_pago'))
                            ->default('PUE'),
                        Select::make('regimen_fiscal_receptor')
                            ->label('Régimen Fiscal Receptor')
                            ->required()
                            ->searchable()
                            ->options(fn (): array => self::opcionesCatalogoSat('sat_regimen_fiscal'))
                            ->default('616')
                            ->live()
                            ->disabled(fn (Get $get): bool => filled($get('cliente_id')))
                            ->dehydrated(),
                        TextInput::make('nombre_emisor')
                            ->label('Razón Social Emisor')
                            ->maxLength(255)
                            ->default(fn () => Configuracion::query()->value('razon_social'))
                            ->readOnly(fn (): bool => filled(Configuracion::query()->value('razon_social')))
                            ->columnSpan(2),
                        TextInput::make('rfc_emisor')
                            ->label('RFC Emisor')
                            ->maxLength(13)
                            ->default(fn () => Configuracion::query()->value('rfc'))
                            ->readOnly(fn (): bool => filled(Configuracion::query()->value('rfc')))
                            ->columnSpan(1),
                        Select::make('regimen_fiscal_emisor')
                            ->label('Régimen Fiscal Emisor')
                            ->searchable()
                            ->options(fn (): array => self::opcionesCatalogoSat('sat_regimen_fiscal'))
                            ->default(fn () => Configuracion::query()->value('regimen'))
                            ->disabled(fn (): bool => filled(Configuracion::query()->value('regimen')))
                            ->dehydrated(),
                        TextInput::make('rfc_receptor')
                            ->label('RFC Receptor')
                            ->required()
                            ->maxLength(13)
                            ->readOnly(fn (Get $get): bool => filled($get('cliente_id')))
                            ->columnSpan(1),
                        TextInput::make('razon_social_receptor')
                            ->label('Razón Social Receptor')
                            ->required()
                            ->maxLength(255)
                            ->readOnly(fn (Get $get): bool => filled($get('cliente_id')))
                            ->columnSpan(2),
                        TextInput::make('cfdi_uuid')
                            ->label('UUID')
                            ->maxLength(36)
                            ->readOnly()
                            ->columnSpan(2),
                        Textarea::make('cfdi_xml')
                            ->label('XML')
                            ->columnSpan(4)
                            ->rows(3)
                            ->readOnly(),
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
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                            ]),
                        TextInput::make('impuestos_total')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                            ->stripCharacters(',')
                            ->default(0.0)
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                            ]),
                        TextInput::make('total')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                            ->stripCharacters(',')
                            ->default(0.0)
                            ->extraAttributes([
                                'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                            ]),
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }
}
