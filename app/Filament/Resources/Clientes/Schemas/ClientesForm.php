<?php

namespace App\Filament\Resources\Clientes\Schemas;

use App\Models\SatRegimenFiscal;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ClientesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                ->tabs([
                    Tab::make('Datos Generales')
                    ->schema([
                        TextInput::make('clave')
                            ->required(),
                        TextInput::make('nombre')
                            ->required()->columnSpan(3),
                        TextInput::make('rfc')
                            ->required(),
                        Select::make('regimen')
                            ->label('Régimen fiscal')
                            ->options(fn () => SatRegimenFiscal::query()
                                ->orderBy('clave')
                                ->get()
                                ->mapWithKeys(fn (SatRegimenFiscal $regimen) => [
                                    $regimen->clave => "{$regimen->clave} - {$regimen->descripcion}",
                                ])
                                ->all())
                            ->preload()
                            ->searchable()
                            ->required(),
                        TextInput::make('telefono')
                            ->tel()
                            ->required(),
                        TextInput::make('correo')
                            ->required(),
                    ])->columnSpanFull()->columns(4),
                    Tab::make('Dirección')
                    ->schema([
                        TextInput::make('calle'),
                        TextInput::make('exterior'),
                        TextInput::make('interior'),
                        TextInput::make('colonia'),
                        TextInput::make('municipio'),
                        TextInput::make('estado'),
                        TextInput::make('pais')->default('MEX'),
                        TextInput::make('codigo')->required(),
                    ])->columnSpanFull()->columns(4),
                    Tab::make('Datos de Venta')
                    ->schema([
                        TextInput::make('descuento')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('lista')
                            ->required()
                            ->numeric()
                            ->default(1),
                        TextInput::make('contacto')
                            ->required(),
                        TextInput::make('dias_credito')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('saldo')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                    ])->columnSpanFull()->columns(3),
                ])->columnSpanFull()
            ]);
    }
}
