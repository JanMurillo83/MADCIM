<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Horarios\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HorariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Horario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable(),
                TextColumn::make('hora_entrada')
                    ->label('Entrada')
                    ->time('H:i'),
                TextColumn::make('hora_salida')
                    ->label('Salida')
                    ->time('H:i'),
                TextColumn::make('tolerancia_entrada')
                    ->label('Tol. entrada')
                    ->suffix(' min'),
                TextColumn::make('duracion_comida')
                    ->label('Comida')
                    ->suffix(' min'),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()->label('Agregar horario'),
            ])
            ->defaultSort('nombre');
    }
}
