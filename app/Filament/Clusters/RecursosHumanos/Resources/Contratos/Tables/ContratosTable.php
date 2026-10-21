<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Contratos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContratosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('empleado.nombre_completo')->label('Empleado'),
                TextColumn::make('tipo')->label('Tipo')->searchable(),
                TextColumn::make('fecha_inicio')->label('Inicio')->date('d/m/Y')->sortable(),
                TextColumn::make('fecha_fin')->label('Vencimiento')->date('d/m/Y')->sortable(),
                TextColumn::make('estatus')->label('Estatus')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vigente' => 'success',
                        'vencido', 'cancelado' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('decision')->label('Renovación')->badge(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()->label('Agregar contrato'),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
