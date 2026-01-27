<?php

namespace App\Filament\Pages;

use App\Models\Configuracion;
use App\Models\Productos;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
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
            ->modalFooterActions([
                Action::make('Productos')
                    ->url('/productos')
                    ->icon('fas-warehouse')
                    ->extraAttributes(['style' => 'width: 150px !important;']),
                Action::make('Clientes')
                    ->url('/clientes')
                    ->icon('fas-users')
                    ->extraAttributes(['style' => 'width: 150px !important;']),
                Action::make('Documentos')
                    ->url('/documentos')
                    ->icon('fas-file-invoice-dollar')
                    ->extraAttributes(['style' => 'width: 150px !important;']),
            ])
            ->schema(function (Schema $schema): Schema {
                return $schema
                    ->components([
                        Tabs::make()
                    ->tabs([
                        Tab::make('Principal')
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
                            ])->columns(3),
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
                                Placeholder::make('Importe M2')->columnSpan(2),
                                TextInput::make('importe_m2')->required()
                                    ->default(0)->prefix('$')
                                    ->columnSpan(2)->readOnly()->hiddenLabel(),
                                Placeholder::make('Importe Deposito')->columnSpan(2),
                                TextInput::make('importe_depo')->required()->default(0)
                                    ->prefix('$')->columnSpan(2)->readOnly()->hiddenLabel(),
                                Placeholder::make('Total a Cobrar')->columnSpan(2),
                                TextInput::make('importe_total')->required()
                                    ->default(0)->prefix('$')->columnSpan(2)
                                    ->readOnly()->hiddenLabel()->hiddenLabel(),
                            ])->columns(4)

                        ])->columns(3),
                        Tab::make('Consultar Precios Madera')
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
                                        $this->imagen_madera = asset('storage/' . $prod->imagen);
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
                        Tab::make('Consultar Precios Equipos')
                            ->schema([]),
                    ])
                ]);
            });
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
        $set('importe_m2',$importe_m2);
        $set('importe_depo',$importe_depo);
        $set('importe_total',$importe_m2 + $importe_depo);
    }



}
