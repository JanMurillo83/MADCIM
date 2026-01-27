<?php

namespace App\Filament\Resources\Clientes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('clave')
                    ->required(),
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('rfc')
                    ->required(),
                TextInput::make('regimen')
                    ->required(),
                TextInput::make('codigo')
                    ->required(),
                TextInput::make('calle')
                    ->required(),
                TextInput::make('exterior')
                    ->required(),
                TextInput::make('interior')
                    ->required(),
                TextInput::make('colonia')
                    ->required(),
                TextInput::make('municipio')
                    ->required(),
                TextInput::make('estado')
                    ->required(),
                TextInput::make('pais')
                    ->required(),
                TextInput::make('telefono')
                    ->tel()
                    ->required(),
                TextInput::make('correo')
                    ->required(),
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
            ]);
    }
}
