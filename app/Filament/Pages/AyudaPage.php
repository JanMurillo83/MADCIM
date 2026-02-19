<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Clientes\Schemas\ClientesForm;
use App\Filament\Resources\Productos\Schemas\ProductosForm;
use App\Models\Clientes;
use App\Models\Configuracion;
use App\Models\Cotizaciones;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Embarque;
use App\Models\EmbarqueItem;
use App\Models\Productos;
use App\Models\RegistroRenta;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use function Termwind\style;

class AyudaPage extends Page implements HasActions
{
    use InteractsWithActions;

    protected static ?string $title = 'Cotizador';
    protected string $view = 'filament.pages.ayuda-page';
    protected static string | BackedEnum | null $navigationIcon = 'fas-question-circle';

    public ?float $po_ent;
    public ?float $po_ped;
    public ?float $imp_tabla_met;
    public ?float $imp_tabla_dep;
    public ?float $imp_triqui_met;
    public ?float $imp_triqui_dep;
    public ?float $imp_tridie_met;
    public ?float $imp_tridie_dep;
    public ?string $imagen_madera;
    public ?string $imagen_quipo;

    public array $sugeridos = [];
    public array $sugeridos2 = [];

    public array $data = ['activeTab' => 1];
    public int $currentTab = 1;

    // Propiedades públicas para los datos del formulario
    public ?array $cotizar = [];
    public ?float $total_renta = 0;
    public ?float $deposito = 0;
    public ?float $importe_total_renta = 0;
    public ?float $importe_venta_total = 0;
    public ?float $m2_cubiertos = 0;

    // Propiedades para capturar importes de la pestaña Principal
    public ?float $importe_m2_principal = 0;
    public ?float $importe_depo_principal = 0;

    public function updatedCurrentTab($value): void
    {
        // Este método se ejecuta cuando currentTab cambia
        $this->dispatch('$refresh');
    }

    public function mount(): void
    {
        $this->defaultAction = 'onboarding';
        $conf = Configuracion::where('id',1)->first();
        $this->po_ent = $conf->por_tab_com;
        $this->po_ped = $conf->por_tab_ped;
        $this->imp_tabla_met = $conf->imp_tabla_met;
        $this->imp_tabla_dep = $conf->imp_tabla_dep;
        $this->imp_triqui_met = $conf->imp_triqui_met;
        $this->imp_triqui_dep = $conf->imp_triqui_dep;
        $this->imp_tridie_met = $conf->imp_tridie_met;
        $this->imp_tridie_dep = $conf->imp_tridie_dep;
        $this->imagen_madera = asset('images/LOGO.png');
        $this->imagen_quipo = asset('images/LOGO.png');
        $this->sugeridos[] = ['id_real' => '11','desc_real'=>'POLIN USADO ENTERO','sugerido'=>'Polin Usado Entero','minimo' => '0','maximo' => '0'];
        $this->sugeridos[] = ['id_real' => '12','desc_real'=>'POLIN USADO  DE 30 A 60 CMS TACONES','sugerido'=>'Polin usado  de 30 a 60 cms tacones','minimo' => '0','maximo' => '0'];
        $this->sugeridos[] = ['id_real' => '32','desc_real'=>'BARROTE USADO DE 80 A 1.00 MTS CACHETERAS','sugerido'=>'Barrote usado de 80 a 1.00 mts cacheteras','minimo' => '0','maximo' => '0'];
        $this->sugeridos2[] = ['id_real' => '47','desc_real'=>'TABLA DE 30 USADA ENTERA','sugerido'=>'Tabla de 30 usada entera','tablas' => '0','metros' => '0','porcentaje' => '20'];
        $this->sugeridos2[] = ['id_real' => '65','desc_real'=>'TABLA DE 25 USADA ENTERA','sugerido'=>'Tabla de 25 usada enteras','tablas' => '0','metros' => '0','porcentaje' => '40'];
        $this->sugeridos2[] = ['id_real' => '83','desc_real'=>'TABLA DE 20 USADA ENTERA','sugerido'=>'Tabla de 20 usada entera','tablas' => '0','metros' => '0','porcentaje' => '40'];
    }

    public function onboardingAction(): Action
    {
        return Action::make('onboarding')
            ->modalHeading('Bienvenido')
            ->closeModalByEscaping(false)
            ->closeModalByClickingAway(false)
            ->modalWidth('full')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->submit(false)
            ->modalCloseButton(false)
            ->modalFooterActions(function () {
                return [
                    Action::make('Productos')
                        ->icon('fas-warehouse')
                        ->modalHeading('Nuevo producto')
                        ->modalWidth('7xl')
                        ->schema(function (Schema $schema): Schema {
                            return ProductosForm::configure($schema);
                        })
                        ->action(function (array $data): void {
                            Productos::create($data);
                        })
                        ->modalSubmitAction(function ($action) {
                            $action->icon('fas-floppy-disk');
                            $action->label('Guardar');
                            $action->extraAttributes(['style' => 'width: 150px !important;']);
                            $action->color('success');
                            return $action;
                        })
                        ->modalCancelAction(function ($action) {
                            $action->icon('fas-ban');
                            $action->label('Cancelar');
                            $action->extraAttributes(['style' => 'width: 150px !important;']);
                            $action->color('danger');
                            return $action;
                        })
                        ->extraAttributes(['style' => 'width: 150px !important;']),
                    Action::make('Clientes')
                        ->icon('fas-users')
                        ->modalHeading('Nuevo cliente')
                        ->modalWidth('7xl')
                        ->schema(function (Schema $schema): Schema {
                            return ClientesForm::configure($schema);
                        })
                        ->action(function (array $data): void {
                            Clientes::create($data);
                        })
                        ->modalSubmitAction(function ($action) {
                            $action->icon('fas-floppy-disk');
                            $action->label('Guardar');
                            $action->extraAttributes(['style' => 'width: 150px !important;']);
                            $action->color('success');
                            return $action;
                        })
                        ->modalCancelAction(function ($action) {
                            $action->icon('fas-ban');
                            $action->label('Cancelar');
                            $action->extraAttributes(['style' => 'width: 150px !important;']);
                            $action->color('danger');
                            return $action;
                        })
                        ->extraAttributes(['style' => 'width: 150px !important;']),
                    Action::make('Cerrar')
                        ->url('/')
                        ->icon('fas-ban')
                        ->extraAttributes(['style' => 'width: 150px !important;']),
                ];
            })
            ->schema(function (Schema $schema): Schema {
                return $schema
                    ->statePath('data')
                    ->components([
                        Tabs::make()
                    ->statePath('activeTab')
                    ->activeTab(1)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->currentTab = (int) $state;
                        $this->dispatch('tab-changed', tab: $this->currentTab);
                    })
                    ->tabs([
                        Tab::make('Principal')->id('1')
                        ->schema([
                            Section::make('Renta de Madera')
                            ->schema([
                                Placeholder::make('Metros Cuadrados')->columnSpan(2),
                                TextInput::make('metros')
                                    ->hiddenLabel()
                                    ->required()->default(0)
                                    ->label('Metros cuadrados')
                                    ->suffix('M2')
                                    ->extraAttributes(['width' => '10rem'])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get,Set $set){
                                        self::calcula($get,$set);
                                    }),
                                Placeholder::make('% Para enviar Tabla Entera')->columnSpan(2),
                                TextInput::make('por_1')
                                    ->required()->default(0)
                                    ->readOnly()
                                    ->hiddenLabel()
                                    ->suffix('%')
                                    ->extraAttributes(['width' => '10rem'])
                                    ->default($this->po_ent),
                                Placeholder::make('M2 que cubre la Tabla Entera')->columnSpan(2)->extraAttributes(['font-weight' => 'bold']),
                                TextInput::make('m_cober1')
                                    ->readOnly()
                                    ->required()->default(0)
                                    ->hiddenLabel()
                                    ->suffix('M2')
                                    ->extraAttributes(['width' => '10rem']),
                                Placeholder::make('% a enviar en pedacería de tabla')->columnSpan(2)->extraAttributes(['font-weight' => 'bold']),
                                TextInput::make('por_2')
                                    ->required()->default(0)
                                    ->readOnly()
                                    ->hiddenLabel()
                                    ->suffix('%')
                                    ->extraAttributes(['width' => '10rem'])
                                    ->default($this->po_ped),
                                Placeholder::make('M2 que cubre la pedacería')->columnSpan(2)->extraAttributes(['font-weight' => 'bold']),
                                TextInput::make('m_cober2')
                                    ->readOnly()
                                    ->required()->default(0)
                                    ->hiddenLabel()
                                    ->suffix('M2')
                                    ->extraAttributes(['width' => '10rem'])
                            ])->columns(3)->columnSpan(2),
                            Section::make('Importes')
                            ->schema([
                                Select::make('tipo_madera')->label('Tipo de Madera')
                                ->options([
                                    '1' => 'm2 Con Tabla',
                                    '2' => 'm2 Con Triplay 15mm',
                                    '3' => 'm2 Con Triplay 18mm',
                                ])->default('1')->columnSpanFull()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get,Set $set){
                                    self::calcula($get,$set);
                                }),
                                Placeholder::make('Importe M2')->columnSpan(1),
                                TextInput::make('importe_m2')->required()
                                    ->default(0)->prefix('$')
                                    ->columnSpan(2)->readOnly()->hiddenLabel()
                                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((float) $state, 2, '.', ','))
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->importe_m2_principal = floatval(str_replace(',', '', $state));
                                    })
                                    ->extraAttributes([
                                        'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;',
                                    ]),
                                Placeholder::make('Importe Deposito')->columnSpan(1),
                                TextInput::make('importe_depo')->required()->default(0)
                                    ->prefix('$')->columnSpan(2)->readOnly()->hiddenLabel()
                                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((float) $state, 2, '.', ','))
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->importe_depo_principal = floatval(str_replace(',', '', $state));
                                    })
                                    ->extraAttributes([
                                        'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;',
                                    ]),
                                Placeholder::make('Total a Cobrar')->columnSpan(1),
                                TextInput::make('importe_total')->required()
                                    ->default(0)->prefix('$')->columnSpan(2)
                                    ->readOnly()->hiddenLabel()
                                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((float) $state, 2, '.', ','))
                                    ->extraAttributes([
                                        'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;',
                                    ]),
                            ])->columns(3)->columnSpan(2),
                            Actions::make([
                                Action::make('guardar_cotizacion')
                                    ->label('Guardar como Cotización')
                                    ->icon('fas-file-invoice')
                                    ->color('info')
                                    ->modalHeading('Seleccionar Cliente y Días de Renta')
                                    ->modalDescription('Seleccione el cliente y días de renta para la cotización')
                                    ->modalWidth('3xl')
                                    ->form([
                                        Select::make('cliente_id')
                                            ->label('Cliente')
                                            ->options(function () {
                                                return Clientes::orderBy('nombre')->pluck('nombre', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->createOptionForm(fn (Schema $schema) => ClientesForm::configure($schema))
                                            ->createOptionUsing(function (array $data) {
                                                $cliente = Clientes::create($data);
                                                return $cliente->id;
                                            })
                                            ->createOptionAction(function ($action) {
                                                $action->modalHeading('Nuevo cliente');
                                                $action->modalWidth('7xl');
                                                return $action;
                                            }),
                                        Hidden::make('dias_renta')
                                            ->default(30),
                                    ])
                                    ->action(function (array $data) {
                                        return $this->guardarCotizacion($data['cliente_id'], $data['dias_renta']);
                                    })
                                    ->modalSubmitActionLabel('Guardar Cotización')
                                    ->extraAttributes(['style' => 'width: 200px !important;']),
                                Action::make('guardar_nota_renta')
                                    ->label('Guardar como Nota Renta')
                                    ->icon('fas-file-invoice-dollar')
                                    ->color('success')
                                    ->modalHeading('Seleccionar Cliente y Días de Renta')
                                    ->modalDescription('Seleccione el cliente y días de renta para la nota de venta renta')
                                    ->modalWidth('3xl')
                                    ->form([
                                        Select::make('cliente_id')
                                            ->label('Cliente')
                                            ->options(function () {
                                                return Clientes::orderBy('nombre')->pluck('nombre', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->createOptionForm(fn (Schema $schema) => ClientesForm::configure($schema))
                                            ->createOptionUsing(function (array $data) {
                                                $cliente = Clientes::create($data);
                                                return $cliente->id;
                                            })
                                            ->createOptionAction(function ($action) {
                                                $action->modalHeading('Nuevo cliente');
                                                $action->modalWidth('7xl');
                                                return $action;
                                            })
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                // Limpiar la dirección cuando cambia el cliente
                                                $set('direccion_entrega_id', null);
                                            }),
                                        Select::make('direccion_entrega_id')
                                            ->label('Dirección de Entrega')
                                            ->options(function (callable $get) {
                                                $clienteId = $get('cliente_id');
                                                if (!$clienteId) {
                                                    return [];
                                                }
                                                return \App\Models\ClienteDireccionEntrega::where('cliente_id', $clienteId)
                                                    ->orderBy('es_principal', 'desc')
                                                    ->orderBy('nombre_direccion')
                                                    ->get()
                                                    ->mapWithKeys(fn ($dir) => [
                                                        $dir->id => ($dir->nombre_direccion ? ($dir->nombre_direccion.' — ') : '') . $dir->direccion_completa,
                                                    ]);
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->hint('Puede agregar una nueva dirección si no existe')
                                            ->createOptionForm(function () {
                                                return [
                                                    TextInput::make('nombre_direccion')->label('Nombre de la Dirección')->maxLength(255),
                                                    TextInput::make('calle')->label('Calle')->required()->maxLength(255),
                                                    TextInput::make('numero_exterior')->label('Número Exterior')->required()->maxLength(255),
                                                    TextInput::make('numero_interior')->label('Número Interior')->maxLength(255),
                                                    TextInput::make('colonia')->label('Colonia')->required()->maxLength(255),
                                                    TextInput::make('municipio')->label('Municipio')->required()->maxLength(255),
                                                    TextInput::make('estado')->label('Estado')->required()->maxLength(255),
                                                    TextInput::make('codigo_postal')->label('Código Postal')->required()->maxLength(255),
                                                    TextInput::make('pais')->label('País')->default('México')->required()->maxLength(255),
                                                    TextInput::make('contacto_nombre')->label('Nombre del Contacto')->maxLength(255),
                                                    TextInput::make('contacto_telefono')->label('Teléfono del Contacto')->tel()->maxLength(25),
                                                ];
                                            })
                                            ->createOptionUsing(function (array $data, callable $get) {
                                                $clienteId = $get('cliente_id');
                                                $data['cliente_id'] = $clienteId;
                                                $direccion = \App\Models\ClienteDireccionEntrega::create($data);
                                                return $direccion->id;
                                            })
                                            ->createOptionAction(function ($action) {
                                                $action->modalHeading('Nueva dirección de entrega');
                                                $action->modalWidth('4xl');
                                                return $action;
                                            }),
                                        Hidden::make('dias_renta')
                                            ->default(30),
                                        Toggle::make('registrar_pago')
                                            ->label('Registrar pago ahora')
                                            ->helperText('Si está activo, se registrará un pago para esta nota al guardarla.')
                                            ->default(false)
                                            ->live(),
                                        Select::make('metodo_pago')
                                            ->label('Método de pago')
                                            ->options([
                                                'Efectivo' => 'Efectivo',
                                                'Transferencia' => 'Transferencia',
                                                'Tarjeta' => 'Tarjeta',
                                                'Cheque' => 'Cheque',
                                            ])
                                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) $get('registrar_pago'))
                                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) $get('registrar_pago')),
                                        TextInput::make('importe_pago')
                                            ->label('Importe del pago')
                                            ->numeric()
                                            ->prefix('$')
                                            ->placeholder('Monto a cobrar de la nota')
                                            ->default(function () {
                                                // Total esperado de la nota = renta (con IVA) + depósito.
                                                // Dado que $importe_m2_principal ya trae la renta con IVA,
                                                // el total es simplemente renta con IVA + depósito.
                                                $rentaConIva = (float) ($this->importe_m2_principal ?? 0);
                                                $deposito = (float) ($this->importe_depo_principal ?? 0);
                                                return round($rentaConIva + $deposito, 2);
                                            })
                                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) $get('registrar_pago'))
                                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) $get('registrar_pago')),
                                        TextInput::make('referencia_pago')
                                            ->label('Referencia (opcional)')
                                            ->maxLength(255)
                                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) $get('registrar_pago')),
                                    ])
                                    ->action(function (array $data,Get $get) {
                                        $sugeridos = $get('Sugeridos');
                                        $sugeridos2 = $get('Sugerido Tablas');
                                        $nota = $this->guardarNotaVentaRenta($data['cliente_id'], $data['direccion_entrega_id'], $data['dias_renta'],$sugeridos,$sugeridos2);
                                        if (!$nota) {
                                            return;
                                        }

                                        // Si el usuario desea registrar el pago inmediatamente
                                        if (!empty($data['registrar_pago'])) {
                                            $metodo = $data['metodo_pago'] ?? null;
                                            $importe = isset($data['importe_pago']) ? (float) $data['importe_pago'] : 0.0;
                                            if (!$metodo || $importe <= 0) {
                                                Notification::make()
                                                    ->title('Pago no registrado')
                                                    ->body('Debe indicar método e importe válidos para registrar el pago.')
                                                    ->warning()->send();
                                                return;
                                            }

                                            $userId = Auth::id();
                                            $cajaId = null;
                                            if ($metodo === 'Efectivo') {
                                                $cajaId = \App\Models\Caja::where('estatus', 'Abierta')
                                                    ->where('usuario_apertura_id', $userId)
                                                    ->value('id');
                                                if (!$cajaId) {
                                                    Notification::make()
                                                        ->title('Caja no disponible')
                                                        ->body('La nota se guardó, pero no tienes una caja abierta. Abre una caja para registrar pagos en efectivo.')
                                                        ->danger()->send();
                                                    return; // No creamos pago sin caja en efectivo
                                                }
                                            }

                                            \App\Models\Pagos::create([
                                                'documento_tipo' => 'notas_venta_renta',
                                                'documento_id' => $nota->id,
                                                'cliente_id' => $nota->cliente_id,
                                                'fecha_pago' => now(),
                                                'forma_pago' => $metodo,
                                                'metodo_pago' => $metodo,
                                                'importe' => $importe,
                                                'referencia' => $data['referencia_pago'] ?? null,
                                                'user_id' => $userId,
                                                'caja_id' => $cajaId,
                                            ]);

                                            Notification::make()
                                                ->title('Pago registrado')
                                                ->body('Se registró el pago y se actualizó el saldo de la nota.')
                                                ->success()->send();
                                        }
                                    })
                                    ->modalSubmitActionLabel('Guardar Nota Renta')
                                    ->extraAttributes(['style' => 'width: 200px !important;']),
                            ]),
                            Repeater::make('Sugeridos')
                                ->table([
                                    Repeater\TableColumn::make('Sugerido')->width('70rem'),
                                    Repeater\TableColumn::make('Minimo Polines'),
                                    Repeater\TableColumn::make('Maximo Polines'),
                                ])
                                ->reorderable(false)
                                ->addable(false)
                                ->deletable(false)
                                ->compact()
                                ->columnSpanFull()
                                ->schema([
                                    Hidden::make('id_real'),
                                    Hidden::make('desc_real'),
                                    TextInput::make('sugerido')->readOnly(),
                                    TextInput::make('minimo')->readOnly(),
                                    TextInput::make('maximo')->readOnly(),
                                ])->default($this->sugeridos),
                            Repeater::make('Sugerido Tablas')
                                ->table([
                                    Repeater\TableColumn::make('Sugerido')->width('70rem'),
                                    Repeater\TableColumn::make('Tablas'),
                                    Repeater\TableColumn::make('M2'),
                                    Repeater\TableColumn::make('%'),
                                ])
                                ->compact()
                                ->reorderable(false)
                                ->addable(false)
                                ->deletable(false)
                                ->columnSpanFull()
                                ->schema([
                                    Hidden::make('id_real'),
                                    Hidden::make('desc_real'),
                                    TextInput::make('sugerido')->readOnly(),
                                    TextInput::make('tablas')->readOnly(),
                                    TextInput::make('metros')->readOnly(),
                                    TextInput::make('porcentaje')->readOnly()->suffix('%'),
                                ])->default($this->sugeridos2)
                        ])->columns(5),
                        Tab::make('Consultar Precios Madera')->id('2')
                            ->schema([
                                Select::make('producto_madera')
                                    ->searchable()
                                    ->options(Productos::where('linea','MADERA')
                                        ->select(DB::raw("CONCAT(clave,' - ',descripcion) as nombre,id"))
                                        ->pluck('nombre','id'))->columnSpan(2)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get,Set $set){
                                        $id = $get('producto_madera');
                                        $prod = Productos::where('id',$id)->first();
                                        $set('cons_mad_clave',$prod->clave);
                                        $set('cons_mad_descr',$prod->descripcion);
                                        $set('cons_mad_renta',$prod->precio_renta_dia);
                                        $set('cons_mad_venta',$prod->precio_venta);
                                        $set('cons_mad_venmi',$prod->precio_venta);
                                        $set('cons_mad_mecu',$prod->m2_cubre);
                                        if($prod->imagen != '' && $prod->imagen != null) $this->imagen_madera = asset('storage/' . $prod->imagen);
                                    }),
                                Section::make('Precios')
                                    ->schema([
                                        Group::make([
                                            TextInput::make('cons_mad_clave')
                                                ->label('Clave')->inlineLabel()
                                                ->readOnly(),
                                            TextInput::make('cons_mad_descr')
                                                ->label('Descripción')->inlineLabel()
                                                ->readOnly(),
                                            TextInput::make('cons_mad_renta')
                                                ->label('Precio Renta x Dia')->inlineLabel()
                                                ->prefix('$')->numeric()
                                                ->readOnly(),
                                            TextInput::make('cons_mad_venta')
                                                ->label('Precio Venta')->inlineLabel()
                                                ->prefix('$')->numeric()
                                                ->readOnly(),
                                            TextInput::make('cons_mad_venmi')
                                                ->label('Precio Venta mínimo')->inlineLabel()
                                                ->prefix('$')->numeric()
                                                ->readOnly(),
                                            TextInput::make('cons_mad_mecu')
                                                ->label('M2 que cubre')->inlineLabel()
                                                ->numeric()
                                                ->readOnly(),

                                        ]),
                                        Placeholder::make('cons_mad_archivo')
                                            ->label('Imagen')
                                            ->content(function (): HtmlString {
                                                return new HtmlString("<img width='200px' src= '" . $this->imagen_madera . "')>");
                                            }),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                            ])->columns(3),
                        Tab::make('Consultar Precios Equipos')->id('3')
                            ->schema([
                                Select::make('producto_equipo')
                                    ->searchable()
                                    ->options(Productos::where('linea','EQUIPO')
                                        ->select(DB::raw("CONCAT(clave,' - ',descripcion) as nombre,id"))
                                        ->pluck('nombre','id'))->columnSpan(2)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get,Set $set){
                                        $id = $get('producto_equipo');
                                        $prod = Productos::where('id',$id)->first();
                                        $set('cons_equi_clave',$prod->clave);
                                        $set('cons_equi_descr',$prod->descripcion);
                                        $set('cons_equi_renta',$prod->precio_renta_dia);
                                        $set('cons_equi_venta',$prod->precio_venta);
                                        $set('cons_equi_venmi',$prod->precio_venta);
                                        $set('cons_equi_mecu',$prod->m2_cubre);
                                        if($prod->imagen != '' && $prod->imagen != null)$this->imagen_quipo = asset('storage/' . $prod->imagen);
                                    }),
                                Section::make('Precios')
                                    ->schema([
                                        Group::make([
                                            TextInput::make('cons_equi_clave')
                                                ->label('Clave')->inlineLabel()
                                                ->readOnly(),
                                            TextInput::make('cons_equi_descr')
                                                ->label('Descripción')->inlineLabel()
                                                ->readOnly(),
                                            TextInput::make('cons_equi_renta')
                                                ->label('Precio Renta x Dia')->inlineLabel()
                                                ->prefix('$')->numeric()
                                                ->readOnly(),
                                            TextInput::make('cons_equi_venta')
                                                ->label('Precio Venta')->inlineLabel()
                                                ->prefix('$')->numeric()
                                                ->readOnly(),
                                            TextInput::make('cons_equi_venmi')
                                                ->label('Precio Venta mínimo')->inlineLabel()
                                                ->prefix('$')->numeric()
                                                ->readOnly(),
                                            TextInput::make('cons_equi_mecu')
                                                ->label('M2 que cubre')->inlineLabel()
                                                ->numeric()
                                                ->readOnly(),

                                        ]),
                                        Placeholder::make('cons_equi_archivo')
                                            ->label('Imagen')
                                            ->content(function (): HtmlString {
                                                return new HtmlString("<img width='200px' src= '" . $this->imagen_quipo . "')>");
                                            }),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])->columns(3),
                        Tab::make('Cotización Libre')->id('4')
                            ->schema([
                                Repeater::make('cotizar')
                                    ->label('Realizar Cotización')
                                    ->compact()
                                    ->reorderable(false)
                                    ->table([
                                        Repeater\TableColumn::make('Cantidad')->width('10rem'),
                                        Repeater\TableColumn::make('Producto')->width('30rem'),
                                        Repeater\TableColumn::make('M2')->width('10rem'),
                                        Repeater\TableColumn::make('Total M2')->width('10rem'),
                                        Repeater\TableColumn::make('Precio Renta')->width('10rem'),
                                        Repeater\TableColumn::make('Importe Renta')->width('10rem'),
                                        Repeater\TableColumn::make('Precio Venta')->width('10rem'),
                                        Repeater\TableColumn::make('Importe Venta')->width('10rem'),
                                    ])
                                    ->schema([
                                        TextInput::make('cantidad')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->default(1)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get,Set $set){
                                                $cve = $get('clave');
                                                $can = $get('cantidad');
                                                $prod = Productos::where('id',$cve)->first();
                                                if($prod == null) return;
                                                $set('descripcion',$prod->descripcion);
                                                $set('precio_renta',$prod->precio_renta_dia);
                                                $set('precio_venta',$prod->precio_venta);
                                                $set('m2',$prod->m2_cubre);
                                                $set('total_m2',$prod->m2_cubre*$can);
                                                $set('importe',$prod->precio_renta_dia*$can);
                                                $set('importe_venta',$prod->precio_venta*$can);
                                                self::calcula2($get,$set);
                                            }),
                                        Select::make('clave')
                                            ->label('Producto')
                                            ->options(function (){
                                                return Productos::select(DB::raw("CONCAT(clave,' - ',descripcion) as descripcion"),'id')
                                                    ->pluck('descripcion','id');
                                            })->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get,Set $set){
                                                $cve = $get('clave');
                                                $can = $get('cantidad');
                                                $prod = Productos::where('id',$cve)->first();
                                                $set('descripcion',$prod->descripcion);
                                                $set('precio_renta',$prod->precio_renta_dia);
                                                $set('precio_venta',$prod->precio_venta);
                                                $set('m2',$prod->m2_cubre);
                                                $set('total_m2',$prod->m2_cubre*$can);
                                                $set('importe',$prod->precio_renta_dia*$can);
                                                $set('importe_venta',$prod->precio_venta*$can);
                                                self::calcula2($get,$set);
                                            }),
                                        Hidden::make('descripcion')
                                            ->label('Descripcion'),
                                        TextInput::make('m2')
                                            ->label('M2')
                                            ->numeric()
                                            ->suffix('M2')
                                            ->default(0)->readOnly(),
                                        TextInput::make('total_m2')
                                            ->label('Total M2')
                                            ->numeric()
                                            ->suffix('M2')
                                            ->default(0)->readOnly(),
                                        TextInput::make('precio_renta')
                                            ->label('Precio de Renta')
                                            ->prefix('$')
                                            ->numeric()
                                            ->default(0)->readOnly(),
                                        TextInput::make('importe')
                                            ->label('Importe')
                                            ->prefix('$')
                                            ->numeric()
                                            ->default(0)->readOnly(),
                                        TextInput::make('precio_venta')
                                            ->label('Precio Venta')
                                            ->prefix('$')
                                            ->numeric()
                                            ->default(0)->readOnly(),
                                        TextInput::make('importe_venta')
                                            ->label('Importe Venta')
                                            ->prefix('$')
                                            ->numeric()
                                            ->default(0)->readOnly(),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull(),
                                Group::make([
                                    TextInput::make('total_renta')
                                        ->label('Total Renta')
                                        ->prefix('$')
                                        ->numeric()
                                        ->default(0)
                                        ->extraAttributes([
                                            'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                                        ]),
                                    TextInput::make('deposito')
                                        ->label('Deposito')
                                        ->prefix('$')
                                        ->numeric()
                                        ->default(0)
                                        ->extraAttributes([
                                            'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                                        ]),
                                    TextInput::make('importe_total_renta')
                                        ->label('Importe')
                                        ->prefix('$')
                                        ->numeric()
                                        ->default(0)
                                        ->extraAttributes([
                                            'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                                        ]),
                                    TextInput::make('importe_venta_total')
                                        ->label('Importe de Venta')
                                        ->prefix('$')
                                        ->numeric()
                                        ->default(0)
                                        ->extraAttributes([
                                            'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                                        ]),
                                    TextInput::make('m2_cubiertos')
                                        ->label('M2 cubiertos')
                                        ->suffix('M2')
                                        ->numeric()
                                        ->default(0)
                                        ->extraAttributes([
                                            'style' => 'background-color: #fff59d; font-weight: bold; font-size: 2rem; text-align: right;width:17rem;',
                                        ]),
                                ])->columns(5)->columnSpanFull(),
                                Actions::make([
                                    Action::make('guardar_cotizacion')
                                        ->label('Guardar como Cotización')
                                        ->icon('fas-file-invoice')
                                        ->color('info')
                                        ->modalHeading('Seleccionar Cliente y Días de Renta')
                                        ->modalDescription('Seleccione el cliente y días de renta para la cotización')
                                        ->modalWidth('3xl')
                                        ->form([
                                            Select::make('cliente_id')
                                                ->label('Cliente')
                                                ->options(function () {
                                                    return Clientes::orderBy('nombre')->pluck('nombre', 'id');
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->createOptionForm(fn (Schema $schema) => ClientesForm::configure($schema))
                                                ->createOptionUsing(function (array $data) {
                                                    $cliente = Clientes::create($data);
                                                    return $cliente->id;
                                                })
                                                ->createOptionAction(function ($action) {
                                                    $action->modalHeading('Nuevo cliente');
                                                    $action->modalWidth('7xl');
                                                    return $action;
                                                }),
                                            Hidden::make('dias_renta')
                                                ->default(30),
                                        ])
                                        ->action(function (array $data) {
                                            return $this->guardarCotizacion($data['cliente_id'], $data['dias_renta']);
                                        })
                                        ->modalSubmitActionLabel('Guardar Cotización')
                                        ->extraAttributes(['style' => 'width: 200px !important;']),
                                    Action::make('guardar_nota_venta')
                                        ->label('Guardar como Nota Venta')
                                        ->icon('fas-file-invoice-dollar')
                                        ->color('primary')
                                        ->modalHeading('Seleccionar Cliente')
                                        ->modalDescription('Seleccione el cliente para la nota de venta')
                                        ->modalWidth('3xl')
                                        ->form([
                                            Select::make('cliente_id')
                                                ->label('Cliente')
                                                ->options(function () {
                                                    return Clientes::orderBy('nombre')->pluck('nombre', 'id');
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->createOptionForm(fn (Schema $schema) => ClientesForm::configure($schema))
                                                ->createOptionUsing(function (array $data) {
                                                    $cliente = Clientes::create($data);
                                                    return $cliente->id;
                                                })
                                                ->createOptionAction(function ($action) {
                                                    $action->modalHeading('Nuevo cliente');
                                                    $action->modalWidth('7xl');
                                                    return $action;
                                                }),
                                        ])
                                        ->action(function (array $data, Get $get) {
                                            $cotizar = $get('cotizar');
                                            return $this->guardarNotaVentaVenta($data['cliente_id'],$cotizar);
                                        })
                                        ->modalSubmitActionLabel('Guardar Nota Venta')
                                        ->extraAttributes(['style' => 'width: 200px !important;']),
                                ])
                            ])
                            ->columns(3),
                    ])
                ]);
            });
    }
    public function calcula2(Get $get,Set $set): void
    {
        $cotiza = $get('../../cotizar');
        //dd($cotiza);
        $total_renta = 0;
        $deposito = 0;
        $metros_cuadrados = 0;
        $importe_venta_total = 0;
        $m2_cubiertos = 0;
        if($cotiza == null) return;
        foreach($cotiza as $c){
            $total_renta += $c['importe'];
            $deposito += $c['importe'] / 2;
            $importe_venta_total += $c['importe_venta'];
            $m2_cubiertos += $c['total_m2'];
        }
        $set('../../total_renta',$total_renta);
        $set('../../deposito',$deposito);
        $set('../../importe_total_renta',$total_renta + $deposito);
        $set('../../m2_cubiertos',$m2_cubiertos);
        $set('../../importe_venta_total',$importe_venta_total);
    }
    public function calcula(Get $get,Set $set): void
    {
        $metros = $get('metros');
        $por_1 = $get('por_1');
        $por_2 = $get('por_2');
        $m_cober1 = $metros*($por_1 * 0.01);
        $m_cober2 = $metros*($por_2 * 0.01);
        $set('m_cober1',$m_cober1);
        $set('m_cober2',$m_cober2);
        $tip = $get('tipo_madera');
        $importe_m2 = 0;
        $importe_depo = 0;
        if($tip == '1'){
            $importe_m2 = $metros * $this->imp_tabla_met;
            $importe_depo = $metros * $this->imp_tabla_dep;
        }
        if($tip == '2'){
            $importe_m2 = $metros * $this->imp_triqui_met;
            $importe_depo = $metros * $this->imp_triqui_dep;
        }
        if($tip == '3'){
            $importe_m2 = $metros * $this->imp_tridie_met;
            $importe_depo = $metros * $this->imp_tridie_dep;
        }
        $importe_m2 = $importe_m2 * 1.16;
        $set('importe_m2',number_format($importe_m2,2));
        $set('importe_depo',number_format($importe_depo,2));
        $set('importe_total',number_format($importe_m2 + $importe_depo,2));

        // Actualizar propiedades de clase para usar en guardarCotizacion
        $this->importe_m2_principal = $importe_m2;
        $this->importe_depo_principal = $importe_depo;

        $suge = $get('Sugeridos');
        $suge = [];
        $suge_1 = $metros * 1.5;
        $suge_2 = $metros * 1;
        $suge_3 = $metros * 2;
        $suge_1_1 = $metros * 2;
        $suge_2_1 = $metros * 1;
        $suge_3_1 = $metros * 2;
        $suge[] = ['id_real' => '11','desc_real'=>'POLIN USADO ENTERO','sugerido'=>'Polín Usado Entero','minimo' => round($suge_1,0),'maximo' => round($suge_1_1,0)];
        $suge[] = ['id_real' => '12','desc_real'=>'POLIN USADO  DE 30 A 60 CMS TACONES','sugerido'=>'Polín usado  de 30 a 60 cms tacones','minimo' => round($suge_2,0),'maximo' => round($suge_2_1,0)];
        $suge[] = ['id_real' => '32','desc_real'=>'BARROTE USADO DE 80 A 1.00 MTS CACHETERAS','sugerido'=>'Barrote usado de 80 a 1.00 mts cacheteras','minimo' => round($suge_3,0),'maximo' => round($suge_3_1,0)];
        $set('Sugeridos',$suge);

        $var_p = ($por_1 * 0.01);
        $var_1 = 0.3 * 2.5;
        $var_2 = 0.25 * 2.5;
        $var_3 = 0.2 * 2.5;


        $var_1_1 = $metros * 0.2;
        $var_2_1 = $metros * 0.4;
        $var_3_1 = $metros * 0.4;

        $var_1_2 = ($metros * $var_p) * 0.2;
        $var_2_2 = ($metros * $var_p) * 0.4;
        $var_3_2 = ($metros * $var_p) * 0.4;

        $var_1_3 = $m_cober1  * 0.2;
        $var_2_3 = $m_cober1  * 0.4;
        $var_3_3 = $m_cober1  * 0.4;

        $suge2 = $get('Sugerido Tablas');
        $suge2 = [];
        $suge2[] = ['id_real' => '47','desc_real'=>'TABLA DE 30 USADA ENTERA','sugerido'=>'Tabla de 30 usada entera','tablas' => round($var_1_1,0),'metros' => round($var_1_3,0),'porcentaje' => '20'];
        $suge2[] = ['id_real' => '65','desc_real'=>'TABLA DE 25 USADA ENTERA','sugerido'=>'Tabla de 25 usada enteras','tablas' => round($var_2_1,0),'metros' => round($var_2_3,0),'porcentaje' => '40'];
        $suge2[] = ['id_real' => '83','desc_real'=>'TABLA DE 20 USADA ENTERA','sugerido'=>'Tabla de 20 usada entera','tablas' => round($var_3_1,0),'metros' => round($var_3_3,0),'porcentaje' => '40'];
        $set('Sugerido Tablas',$suge2);

    }

    public function guardarCotizacion(int $clienteId, int $diasRenta = 1): void
    {
        if ($this->importe_m2_principal <= 0 && $this->importe_depo_principal <= 0) {
            Notification::make()
                ->title('Error')
                ->body('Debe calcular los importes en la pestaña Principal antes de guardar.')
                ->danger()
                ->send();
            return;
        }

        DB::transaction(function () use ($clienteId, $diasRenta) {
            // Serie fija para cotizaciones
            $serieDefault = 'M';

            // Calcular importes multiplicados por días de renta
            $importeRenta = ($this->importe_m2_principal / 1.16);
            $importeDeposito = $this->importe_depo_principal;
            $subtotal = $importeRenta  + $importeDeposito;

            // Calcular IVA (16%)
            $iva = $subtotal * 0.16;
            $total = $subtotal + $iva;

            // Crear cotización
            $cotizacion = Cotizaciones::create([
                'cliente_id' => $clienteId,
                'serie' => $serieDefault,
                'folio' => null, // Se asigna automáticamente
                'fecha_emision' => now(),
                'moneda' => 'MXN',
                'tipo_cambio' => 1.0,
                'subtotal' => $subtotal,
                'impuestos_total' => $iva,
                'total' => $total,
                'estatus' => 'Activa',
                'dias_renta' => $diasRenta,
            ]);

            // Crear partida para RENTA
            $ivaRenta = $importeRenta * 0.16;
            $cotizacion->partidas()->create([
                'cantidad' => 1,
                'item' => 143,
                'descripcion' => 'RENTA DE MADERA x M2',
                'valor_unitario' => $importeRenta,
                'subtotal' => $importeRenta,
                'impuestos' => $ivaRenta,
                'total' => $importeRenta + $ivaRenta,
            ]);

            // Crear partida para DEPOSITO
            $ivaDeposito = $importeDeposito * 0.16;
            $cotizacion->partidas()->create([
                'cantidad' => 1,
                'item' => 142,
                'descripcion' => 'DEPOSITO EN GARANTIA',
                'valor_unitario' => $importeDeposito,
                'subtotal' => $importeDeposito,
                'impuestos' => $ivaDeposito,
                'total' => $importeDeposito + $ivaDeposito,
            ]);

            Notification::make()
                ->title('Cotización creada')
                ->body("Se creó la cotización: {$cotizacion->serie}-{$cotizacion->folio}")
                ->success()
                ->send();
        });
    }

    public function guardarNotaVentaRenta(int $clienteId, int $direccionEntregaId, int $diasRenta = 1, array $sugeridos, array $sugeridos2): ?\App\Models\NotasVentaRenta
    {
        if ($this->importe_m2_principal <= 0 && $this->importe_depo_principal <= 0) {
            Notification::make()
                ->title('Error')
                ->body('Debe calcular los importes en la pestaña Principal antes de guardar.')
                ->danger()
                ->send();
            return null;
        }

        $serieDefault = 'M';

        // Calcular importes multiplicados por días de renta
        $importeRenta = ($this->importe_m2_principal / 1.16);
        $importeDeposito = $this->importe_depo_principal;

        // Calcular IVA solo sobre la renta (el depósito NO lleva IVA)
        $ivaRenta = $importeRenta * 0.16;

        // Subtotal = solo renta (sin depósito)
        $subtotal = $importeRenta;

        // Total = Subtotal + IVA Renta + Depósito (sin IVA)
        $total = $subtotal + $ivaRenta + $importeDeposito;

        // Obtener datos del cliente
        $cliente = Clientes::find($clienteId);

        // Crear nota de venta renta
        $notaVenta = NotasVentaRenta::create([
            'cliente_id' => $clienteId,
            'direccion_entrega_id' => $direccionEntregaId,
            'serie' => $serieDefault,
            'folio' => null, // Se asigna automáticamente
            'fecha_emision' => now(),
            'dias_renta' => $diasRenta,
            'moneda' => 'MXN',
            'tipo_cambio' => 1.0,
            'deposito' => $importeDeposito,
            'subtotal' => $subtotal,
            'impuestos_total' => $ivaRenta,
            'total' => $total,
            'saldo_pendiente' => $total,
            'estatus' => 'Activa',
        ]);

        // Crear Embarque programado automáticamente para esta nota
        try {
            $embarque = Embarque::create([
                'folio' => null, // si existe autogeneración
                'fecha_programada' => now(),
                'vehiculo' => null,
                'chofer_id' => null,
                'estatus' => 'Programado',
                'cliente_id' => $clienteId,
                'direccion_entrega_id' => $direccionEntregaId,
                'observaciones' => 'Generado automáticamente desde AyudaPage',
                'user_id_creador' => Auth::id(),
            ]);

            EmbarqueItem::create([
                'embarque_id' => $embarque->id,
                'documento_tipo' => 'notas_venta_renta',
                'documento_id' => $notaVenta->id,
                'cantidad_programada' => 1,
                'entregado' => false,
            ]);
        } catch (\Throwable $e) {
            // No bloquear la creación de la nota si falla la integración a embarques
            \Filament\Notifications\Notification::make()
                ->title('Aviso: No se pudo programar el embarque automáticamente')
                ->body('Podrá programarse manualmente más tarde. Detalle: '.($e->getMessage()))
                ->warning()
                ->send();
        }

        // Crear partida para RENTA (sin incluir depósito)
        $notaVenta->partidas()->create([
            'cantidad' => 1,
            'item' => 143,
            'descripcion' => 'RENTA DE MADERA x M2',
            'valor_unitario' => $importeRenta,
            'subtotal' => $importeRenta,
            'impuestos' => $ivaRenta,
            'total' => $importeRenta + $ivaRenta,
        ]);

        // Mapeo de items sugeridos con sus claves (usando coincidencia parcial)
            foreach ($sugeridos as $sugerido) {
                $descripcion = $sugerido['sugerido'] ?? '';
                $cantidad = max((int)($sugerido['minimo'] ?? 0), (int)($sugerido['maximo'] ?? 0));
                if ($cantidad > 0 && !empty($descripcion)) {
                    $clave = $sugerido['id_real'] ?? null;
                    if ($clave) {
                        // Buscar el producto por clave
                        $producto = Productos::where('id', $clave)->first();
                        $reg_1 = RegistroRenta::create([
                            'nota_venta_renta_id' => $notaVenta->id,
                            'cliente_id' => $clienteId,
                            'cliente_nombre' => $cliente->nombre,
                            'cliente_contacto' => $cliente->contacto,
                            'cliente_telefono' => $cliente->telefono,
                            'cliente_direccion' => $cliente->direccion,
                            'producto_id' => $producto ? $producto->id : null,
                            'cantidad' => $cantidad,
                            'dias_renta' => $diasRenta,
                            'fecha_renta' => now(),
                            'fecha_vencimiento' => now()->addDays($diasRenta),
                            'importe_renta' => 0,
                            'importe_deposito' => 0,
                            'estado' => 'Activo',
                            'observaciones' => $descripcion,
                        ]);
                    }
                }
            }

        // Registrar items de tablas

            foreach ($sugeridos2 as $tabla) {
                $descripcion = $tabla['sugerido'] ?? '';
                $cantidad = (int)($tabla['tablas'] ?? 0);

                if ($cantidad > 0 && !empty($descripcion)) {
                    $clave = $tabla['id_real'] ?? null;
                    if ($clave) {
                        // Buscar el producto por clave
                        $producto = Productos::where('id', $clave)->first();

                        $reg_2 = RegistroRenta::create([
                            'nota_venta_renta_id' => $notaVenta->id,
                            'cliente_id' => $clienteId,
                            'cliente_nombre' => $cliente->nombre,
                            'cliente_contacto' => $cliente->contacto,
                            'cliente_telefono' => $cliente->telefono,
                            'cliente_direccion' => $cliente->direccion,
                            'producto_id' => $producto ? $producto->id : null,
                            'cantidad' => $cantidad,
                            'dias_renta' => $diasRenta,
                            'fecha_renta' => now(),
                            'fecha_vencimiento' => now()->addDays($diasRenta),
                            'importe_renta' => 0,
                            'importe_deposito' => 0,
                            'estado' => 'Activo',
                            'observaciones' => $descripcion,
                        ]);
                    }
                }
            }


        // Redirigir a la vista previa
        Notification::make()
            ->title('Nota de Venta Renta creada')
            ->body("Se creó la nota: {$notaVenta->serie}-{$notaVenta->folio}. Redirigiendo a vista previa...")
            ->success()
            ->send();

        // Redirección usando JavaScript
        $this->dispatch('redirect', route('notas-venta-renta.preview', $notaVenta->id));

        return $notaVenta;
    }

    public function guardarNotaVentaVenta(int $clienteId,array $cotizar): void
    {
        // Obtener datos del repeater cotizar desde el schema
        if (empty($cotizar)) {
            Notification::make()
                ->title('Error')
                ->body('Debe agregar al menos un producto en la Cotización Libre.')
                ->danger()
                ->send();
            return;
        }

        DB::transaction(function () use ($clienteId, $cotizar) {
            // Serie fija para notas de venta
            $serieDefault = 'M';

            // Calcular totales
            $subtotal = 0;
            foreach ($cotizar as $partida) {
                $subtotal += (float)($partida['importe_venta'] ?? 0);
            }

            // Calcular IVA (16%)
            $iva = $subtotal * 0.16;
            $total = $subtotal + $iva;

            // Crear nota de venta venta
            $notaVenta = NotasVentaVenta::create([
                'cliente_id' => $clienteId,
                'serie' => $serieDefault,
                'folio' => null, // Se asigna automáticamente
                'fecha_emision' => now(),
                'moneda' => 'MXN',
                'tipo_cambio' => 1.0,
                'subtotal' => $subtotal,
                'impuestos_total' => $iva,
                'total' => $total,
                'saldo_pendiente'=>$total,
                'estatus' => 'Activa',
            ]);

            // Crear partidas
            foreach ($cotizar as $partida) {
                $precioVenta = (float)($partida['precio_venta'] ?? 0);
                $cantidad = (float)($partida['cantidad'] ?? 1);
                $subtotalPartida = $precioVenta * $cantidad;
                $ivaPartida = $subtotalPartida * 0.16;

                $notaVenta->partidas()->create([
                    'cantidad' => $cantidad,
                    'item' => $partida['clave'] ?? '',
                    'descripcion' => $partida['descripcion'] ?? '',
                    'valor_unitario' => $precioVenta,
                    'subtotal' => $subtotalPartida,
                    'impuestos' => $ivaPartida,
                    'total' => $subtotalPartida + $ivaPartida,
                ]);
            }

            Notification::make()
                ->title('Nota de Venta creada')
                ->body("Se creó la nota: {$notaVenta->serie}-{$notaVenta->folio}")
                ->success()
                ->send();
        });
    }

    private function getDefaultSerie(string $documentoTipo): string
    {
        $serie = \App\Models\DocumentoSerie::where('documento_tipo', $documentoTipo)
            ->orderBy('serie')
            ->first();

        if (!$serie) {
            // Crear una serie por defecto si no existe
            $serie = \App\Models\DocumentoSerie::create([
                'documento_tipo' => $documentoTipo,
                'serie' => 'M',
                'descripcion' => 'Serie por defecto',
                'ultimo_folio' => 0,
            ]);
        }

        return $serie->serie;
    }

}
