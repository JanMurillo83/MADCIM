<?php

namespace App\Filament\Resources\Proveedores\Schemas;

use App\Models\Proveedores;
use App\Models\SatRegimenFiscal;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProveedoresForm
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
                                    ->required()
                                    ->default(fn () => Proveedores::all()->count() + 1),
                                TextInput::make('nombre')
                                    ->required()
                                    ->columnSpan(3),
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
                            ])
                            ->columnSpanFull()
                            ->columns(4),
                        Tab::make('Direccion')
                            ->schema([
                                TextInput::make('calle'),
                                TextInput::make('exterior'),
                                TextInput::make('interior'),
                                TextInput::make('colonia'),
                                TextInput::make('municipio'),
                                TextInput::make('estado'),
                                TextInput::make('pais')->default('MEX'),
                                TextInput::make('codigo')->required(),
                            ])
                            ->columnSpanFull()
                            ->columns(4),
                        Tab::make('Datos de Compra')
                            ->schema([
                                TextInput::make('descuento')
                                    ->required()
                                    ->numeric()
                                    ->default(0.0),
                                Hidden::make('lista')
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
                            ])
                            ->columnSpanFull()
                            ->columns(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
