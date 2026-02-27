<?php

namespace App\Filament\Resources\Usuarios\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsuariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rol')
                    ->sortable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options([
                        'Administrador' => 'Administrador',
                        'Supervisor' => 'Supervisor',
                        'Cajero' => 'Cajero',
                        'Vendedor' => 'Vendedor',
                        'Almacen' => 'Almacen',
                        'Entregas' => 'Entregas',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ], RecordActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo')
                    ->icon('fas-circle-plus'),
            ], HeaderActionsPosition::Bottom);
    }
}
