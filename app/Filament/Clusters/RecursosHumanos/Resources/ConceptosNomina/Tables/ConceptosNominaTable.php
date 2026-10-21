<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\ConceptosNomina\Tables;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class ConceptosNominaTable
{
    public static function configure(Table $table): Table { return $table->columns([
        TextColumn::make('periodo.fecha_inicio')->label('Inicio')->date('d/m/Y')->sortable(),
        TextColumn::make('periodo.fecha_fin')->label('Fin')->date('d/m/Y'),
        TextColumn::make('empleado.nombre_completo')->label('Empleado'),
        TextColumn::make('tipo')->label('Tipo')->badge()->color(fn (string $state): string => $state === 'bono' ? 'success' : 'danger'),
        TextColumn::make('concepto')->searchable(),
        TextColumn::make('importe')->label('Importe')->money('MXN'),
    ])->recordActions([EditAction::make()->visible(fn ($record): bool => $record->periodo?->estatus === 'abierto')])->toolbarActions([CreateAction::make()->label('Agregar concepto')])->defaultSort('created_at','desc'); }
}
