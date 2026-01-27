<?php

namespace App\Filament\Resources\Clientes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clave')
                    ->searchable(),
                TextColumn::make('nombre')
                    ->searchable(),
                TextColumn::make('rfc')
                    ->searchable(),
                TextColumn::make('regimen')
                    ->searchable(),
                TextColumn::make('codigo')
                    ->searchable(),
                TextColumn::make('calle')
                    ->searchable(),
                TextColumn::make('exterior')
                    ->searchable(),
                TextColumn::make('interior')
                    ->searchable(),
                TextColumn::make('colonia')
                    ->searchable(),
                TextColumn::make('municipio')
                    ->searchable(),
                TextColumn::make('estado')
                    ->searchable(),
                TextColumn::make('pais')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->searchable(),
                TextColumn::make('correo')
                    ->searchable(),
                TextColumn::make('descuento')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lista')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('contacto')
                    ->searchable(),
                TextColumn::make('dias_credito')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('saldo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
