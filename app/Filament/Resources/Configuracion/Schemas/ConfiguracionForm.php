<?php

namespace App\Filament\Resources\Configuracion\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ConfiguracionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs([
                        Tab::make('Datos Fiscales')
                            ->schema([
                                TextInput::make('razon_social')
                                    ->label('Razon social')
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('rfc')
                                    ->label('RFC')
                                    ->required(),
                                TextInput::make('regimen')
                                    ->label('Regimen')
                                    ->required(),
                                TextInput::make('codigo')
                                    ->label('Codigo postal')
                                    ->required(),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                        Tab::make('Direccion')
                            ->schema([
                                TextInput::make('calle')
                                    ->label('Calle'),
                                TextInput::make('exterior')
                                    ->label('Exterior'),
                                TextInput::make('interior')
                                    ->label('Interior'),
                                TextInput::make('colonia')
                                    ->label('Colonia'),
                                TextInput::make('municipio')
                                    ->label('Municipio'),
                                TextInput::make('estado')
                                    ->label('Estado'),
                                TextInput::make('pais')
                                    ->label('Pais'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                        Tab::make('Sellos y API')
                            ->schema([
                                FileUpload::make('sello_cer')
                                    ->label('Sello CER')
                                    ->disk('local')
                                    ->directory('configuracion')
                                    ->preserveFilenames()
                                    ->downloadable()
                                    ->columnSpanFull(),
                                FileUpload::make('sello_key')
                                    ->label('Sello KEY')
                                    ->disk('local')
                                    ->directory('configuracion')
                                    ->preserveFilenames()
                                    ->downloadable()
                                    ->columnSpanFull(),
                                TextInput::make('sello_pass')
                                    ->label('Sello PASS')
                                    ->password()
                                    ->revealable(),
                                TextInput::make('api_key')
                                    ->label('API key')
                                    ->password()
                                    ->revealable(),
                                FileUpload::make('logo')
                                    ->label('Logo')
                                    ->disk('public')
                                    ->directory('configuracion')
                                    ->image()
                                    ->downloadable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                        Tab::make('Porcentajes e Importes')
                            ->schema([
                                TextInput::make('por_tab_com')
                                    ->label('Porcentaje tabla compra')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('por_tab_ped')
                                    ->label('Porcentaje tabla pedido')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('imp_tabla_met')
                                    ->label('Importe tabla metro')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('imp_tabla_dep')
                                    ->label('Importe tabla deposito')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('imp_triqui_met')
                                    ->label('Importe triqui metro')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('imp_triqui_dep')
                                    ->label('Importe triqui deposito')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('imp_tridie_met')
                                    ->label('Importe tridie metro')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('imp_tridie_dep')
                                    ->label('Importe tridie deposito')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
