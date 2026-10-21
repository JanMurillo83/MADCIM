<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\DispositivosBiometricos\Schemas;

use App\Models\Sucursal;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DispositivoBiometricoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre')->required()->maxLength(120),
            TextInput::make('numero_serie')->label('Número de serie')->required()->maxLength(120),
            TextInput::make('modelo')->label('Modelo')->default('ZKTeco MB160ID')->maxLength(120),
            Select::make('sucursal_id')
                ->label('Sucursal')
                ->options(fn (): array => Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                ->searchable()
                ->required(),
            Toggle::make('activo')->label('Dispositivo activo')->default(true),
        ]);
    }
}
