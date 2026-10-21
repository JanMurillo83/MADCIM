<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\VinculacionesBiometricas\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VinculacionesBiometricasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('empleado.numero_empleado')->label('Número')->searchable(),
                TextColumn::make('empleado.nombre_completo')->label('Empleado')->searchable(),
                TextColumn::make('dispositivo.nombre')->label('Dispositivo'),
                TextColumn::make('dispositivo.numero_serie')->label('Serie'),
                TextColumn::make('identificador')->label('Identificador')->searchable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([CreateAction::make()->label('Agregar vinculación')]);
    }
}
