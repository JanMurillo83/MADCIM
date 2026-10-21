<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MovimientosLaboralesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('empleado.nombre_completo')->label('Empleado'),
            TextColumn::make('fecha_movimiento')->label('Fecha')->date('d/m/Y')->sortable(),
            TextColumn::make('tipo')->label('Tipo')->badge(),
            TextColumn::make('sucursalOrigen.nombre')->label('Origen')->placeholder('—'),
            TextColumn::make('sucursalDestino.nombre')->label('Destino')->placeholder('—'),
            TextColumn::make('puesto')->placeholder('Sin puesto'),
            TextColumn::make('motivo')->limit(40),
        ])->recordActions([EditAction::make()])->defaultSort('fecha_movimiento', 'desc');
    }
}
