<?php

namespace App\Filament\Resources\Sucursales\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SucursalesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos de la Sucursal')
                ->schema([
                    TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('codigo')
                        ->label('Código')
                        ->maxLength(50),
                    Textarea::make('direccion')
                        ->label('Dirección')
                        ->rows(2)
                        ->maxLength(255),
                    TextInput::make('telefono')
                        ->label('Teléfono')
                        ->maxLength(50),
                    Toggle::make('activa')
                        ->label('Activa')
                        ->default(true),
                ])->columns(2),
        ]);
    }
}
