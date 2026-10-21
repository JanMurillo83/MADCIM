<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\DispositivosBiometricos\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DispositivosBiometricosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Dispositivo')->searchable()->sortable(),
                TextColumn::make('numero_serie')->label('Serie')->searchable(),
                TextColumn::make('modelo')->label('Modelo'),
                TextColumn::make('sucursal.nombre')->label('Sucursal'),
                IconColumn::make('activo')->label('Activo')->boolean(),
                TextColumn::make('ultima_conexion_en')->label('Última conexión')->dateTime('d/m/Y H:i')->placeholder('Sin conexión'),
                TextColumn::make('ultima_checada_en')->label('Última checada')->dateTime('d/m/Y H:i')->placeholder('Sin checadas'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([CreateAction::make()->label('Agregar dispositivo')]);
    }
}
