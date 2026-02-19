<?php

namespace App\Filament\Resources\ClienteDireccionEntregas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ClienteDireccionEntregasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nombre_direccion')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('direccion_completa')
                    ->label('Dirección')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->direccion_completa),

                IconColumn::make('es_principal')
                    ->label('Principal')
                    ->boolean(),

                IconColumn::make('activa')
                    ->label('Activa')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('es_principal')
                    ->label('Dirección Principal'),

                TernaryFilter::make('activa')
                    ->label('Activa'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
