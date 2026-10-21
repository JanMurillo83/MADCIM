<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentoEmpleadosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('empleado.nombre_completo')->label('Empleado'),
                TextColumn::make('tipo')->label('Tipo')->badge(),
                TextColumn::make('nombre')->label('Documento')->searchable(),
                TextColumn::make('fecha_vencimiento')->label('Vencimiento')->date('d/m/Y')->sortable(),
                TextColumn::make('estatus')->label('Estatus')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vigente' => 'success',
                        'vencido', 'cancelado' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
