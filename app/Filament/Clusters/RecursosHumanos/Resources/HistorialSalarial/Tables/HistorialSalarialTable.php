<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HistorialSalarialTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('empleado.nombre_completo')->label('Empleado'),
            TextColumn::make('fecha_inicio')->label('Desde')->date('d/m/Y')->sortable(),
            TextColumn::make('fecha_fin')->label('Hasta')->date('d/m/Y')->placeholder('Actual'),
            TextColumn::make('sueldo_diario')->label('Sueldo diario')->money('MXN'),
            TextColumn::make('sueldo_semanal')->label('Sueldo semanal')->money('MXN'),
            TextColumn::make('motivo')->limit(45),
        ])->recordActions([EditAction::make()])->toolbarActions([
            CreateAction::make()->label('Agregar historial salarial'),
        ])->defaultSort('fecha_inicio', 'desc');
    }
}
