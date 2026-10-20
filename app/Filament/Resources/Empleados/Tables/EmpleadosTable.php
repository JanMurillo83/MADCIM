<?php

namespace App\Filament\Resources\Empleados\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmpleadosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_empleado')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombre_completo')
                    ->label('Empleado')
                    ->state(fn ($record): string => $record->nombre_completo)
                    ->searchable(['nombre', 'apellido_paterno', 'apellido_materno'])
                    ->sortable(['apellido_paterno', 'nombre']),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable(),
                TextColumn::make('departamento')
                    ->label('Área')
                    ->placeholder('Sin asignar'),
                TextColumn::make('puesto')
                    ->placeholder('Sin asignar'),
                TextColumn::make('fecha_ingreso')
                    ->label('Ingreso')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('estatus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'baja' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('apellido_paterno');
    }
}
