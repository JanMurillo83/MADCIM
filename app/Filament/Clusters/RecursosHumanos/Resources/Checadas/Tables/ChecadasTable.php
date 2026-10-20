<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Checadas\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChecadasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('empleado.nombre_completo')
                    ->label('Empleado')
                    ->state(fn ($record): string => $record->empleado?->nombre_completo ?? 'Sin empleado'),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal'),
                TextColumn::make('registrada_en')
                    ->label('Fecha y hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Movimiento')
                    ->formatStateUsing(fn ($state): string => $state?->label() ?? (string) $state)
                    ->badge(),
                TextColumn::make('metodo')
                    ->label('Método')
                    ->formatStateUsing(fn ($state): string => $state?->label() ?? (string) $state),
                TextColumn::make('dispositivo')
                    ->placeholder('No indicado'),
            ])
            ->defaultSort('registrada_en', 'desc');
    }
}
