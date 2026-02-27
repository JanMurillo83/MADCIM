<?php

namespace App\Filament\Resources\Proveedores\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class ProveedoresTable
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
            ->recordActions([
                EditAction::make(),
            ], RecordActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make()
                    ->createAnother(false)
                    ->label('Nuevo')
                    ->icon('fas-circle-plus')
                    ->modalWidth('7xl'),
            ], HeaderActionsPosition::Bottom);
    }
}
