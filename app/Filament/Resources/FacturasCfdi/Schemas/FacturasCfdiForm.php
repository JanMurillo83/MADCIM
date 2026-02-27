<?php

namespace App\Filament\Resources\FacturasCfdi\Schemas;

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

class FacturasCfdiForm
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
                        Select::make('sucursal_id')
                            ->label('Sucursal')
                            ->options(fn () => Sucursal::orderBy('nombre')->pluck('nombre', 'id'))
                            ->searchable()
                            ->preload(),
                        Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
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
                        Select::make('uso_cfdi')
                            ->label('Uso CFDI')
                            ->required()
                            ->searchable()
                            ->options([
                                'G01' => 'G01 - Adquisición de mercancías',
                                'G02' => 'G02 - Devoluciones, descuentos o bonificaciones',
                                'G03' => 'G03 - Gastos en general',
                                'I01' => 'I01 - Construcciones',
                                'I02' => 'I02 - Mobilario y equipo de oficina por inversiones',
                                'I03' => 'I03 - Equipo de transporte',
                                'I04' => 'I04 - Equipo de computo y accesorios',
                                'I05' => 'I05 - Dados, troqueles, moldes, matrices y herramental',
                                'I06' => 'I06 - Comunicaciones telefónicas',
                                'I07' => 'I07 - Comunicaciones satelitales',
                                'I08' => 'I08 - Otra maquinaria y equipo',
                                'D01' => 'D01 - Honorarios médicos, dentales y gastos hospitalarios',
                                'D02' => 'D02 - Gastos médicos por incapacidad o discapacidad',
                                'D03' => 'D03 - Gastos funerales',
                                'D04' => 'D04 - Donativos',
                                'D05' => 'D05 - Intereses reales efectivamente pagados por créditos hipotecarios',
                                'D06' => 'D06 - Aportaciones voluntarias al SAR',
                                'D07' => 'D07 - Primas por seguros de gastos médicos',
                                'D08' => 'D08 - Gastos de transportación escolar obligatoria',
                                'D09' => 'D09 - Depósitos en cuentas para el ahorro',
                                'D10' => 'D10 - Pagos por servicios educativos',
                                'S01' => 'S01 - Sin efectos fiscales',
                                'CP01' => 'CP01 - Pagos',
                                'CN01' => 'CN01 - Nómina',
                            ])
                            ->default('G01'),
                        Select::make('forma_pago')
                            ->label('Forma de Pago')
                            ->required()
                            ->searchable()
                            ->options([
                                '01' => '01 - Efectivo',
                                '02' => '02 - Cheque nominativo',
                                '03' => '03 - Transferencia electrónica de fondos',
                                '04' => '04 - Tarjeta de crédito',
                                '05' => '05 - Monedero electrónico',
                                '06' => '06 - Dinero electrónico',
                                '08' => '08 - Vales de despensa',
                                '12' => '12 - Dación en pago',
                                '13' => '13 - Pago por subrogación',
                                '14' => '14 - Pago por consignación',
                                '15' => '15 - Condonación',
                                '17' => '17 - Compensación',
                                '23' => '23 - Novación',
                                '24' => '24 - Confusión',
                                '25' => '25 - Remisión de deuda',
                                '26' => '26 - Prescripción o caducidad',
                                '27' => '27 - A satisfacción del acreedor',
                                '28' => '28 - Tarjeta de débito',
                                '29' => '29 - Tarjeta de servicios',
                                '30' => '30 - Aplicación de anticipos',
                                '31' => '31 - Intermediario pagos',
                                '99' => '99 - Por definir',
                            ])
                            ->default('01'),
                        Select::make('metodo_pago')
                            ->label('Método de Pago')
                            ->required()
                            ->searchable()
                            ->options([
                                'PUE' => 'PUE - Pago en una sola exhibición',
                                'PPD' => 'PPD - Pago en parcialidades o diferido',
                            ])
                            ->default('PUE'),
                        Select::make('regimen_fiscal_receptor')
                            ->label('Régimen Fiscal Receptor')
                            ->required()
                            ->searchable()
                            ->options([
                                '601' => '601 - General de Ley Personas Morales',
                                '603' => '603 - Personas Morales con Fines no Lucrativos',
                                '605' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios',
                                '606' => '606 - Arrendamiento',
                                '607' => '607 - Régimen de Enajenación o Adquisición de Bienes',
                                '608' => '608 - Demás ingresos',
                                '610' => '610 - Residentes en el Extranjero sin Establecimiento Permanente en México',
                                '611' => '611 - Ingresos por Dividendos (socios y accionistas)',
                                '612' => '612 - Personas Físicas con Actividades Empresariales y Profesionales',
                                '614' => '614 - Ingresos por intereses',
                                '615' => '615 - Régimen de los ingresos por obtención de premios',
                                '616' => '616 - Sin obligaciones fiscales',
                                '620' => '620 - Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
                                '621' => '621 - Incorporación Fiscal',
                                '622' => '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
                                '623' => '623 - Opcional para Grupos de Sociedades',
                                '624' => '624 - Coordinados',
                                '625' => '625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
                                '626' => '626 - Régimen Simplificado de Confianza',
                            ])
                            ->default('616'),
                        TextInput::make('rfc_emisor')
                            ->label('RFC Emisor')
                            ->maxLength(13)
                            ->columnSpan(1),
                        TextInput::make('rfc_receptor')
                            ->label('RFC Receptor')
                            ->required()
                            ->maxLength(13)
                            ->columnSpan(1),
                        TextInput::make('razon_social_receptor')
                            ->label('Razón Social Receptor')
                            ->required()
                            ->maxLength(255)
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
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }
}
