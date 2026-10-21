<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\SaldoVacaciones\Tables;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class SaldoVacacionesTable
{
    public static function configure(Table $table): Table { return $table->columns([
        TextColumn::make('empleado.nombre_completo')->label('Empleado'),
        TextColumn::make('anio')->label('Año')->sortable(),
        TextColumn::make('dias_asignados')->label('Asignados'),
        TextColumn::make('dias_usados')->label('Usados'),
        TextColumn::make('dias_disponibles')->label('Disponibles')->state(fn ($record): float => $record->dias_disponibles)->badge()->color('success'),
    ])->recordActions([EditAction::make()])->defaultSort('anio','desc'); }
}
