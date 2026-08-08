<?php

namespace App\Filament\Resources\Inventario\Tables;

use App\Support\Numero;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventarioTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('producto.clave')
                    ->label('Clave')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('producto.descripcion')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'salida' => 'danger',
                        'ajuste' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->numeric(decimalPlaces: 4, thousandsSeparator: ',')
                    ->sortable(),
                TextColumn::make('existencia_antes')
                    ->label('Existencia Antes')
                    ->numeric(decimalPlaces: 4, thousandsSeparator: ',')
                    ->sortable(),
                TextColumn::make('existencia_despues')
                    ->label('Existencia Después')
                    ->numeric(decimalPlaces: 4, thousandsSeparator: ',')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('documento_referencia')
                    ->label('Referencia')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('fecha_movimiento')
                    ->label('Fecha Movimiento')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha_movimiento', 'desc')
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->modalWidth('lg'),
            ]);
    }
}
