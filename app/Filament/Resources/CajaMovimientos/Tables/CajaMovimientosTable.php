<?php

namespace App\Filament\Resources\CajaMovimientos\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CajaMovimientosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('caja.nombre')->label('Caja')->searchable(),
                BadgeColumn::make('tipo')->colors([
                    'success' => 'Ingreso',
                    'danger' => 'Egreso',
                    'warning' => 'Ajuste',
                ]),
                TextColumn::make('metodo_pago')->label('Método'),
                TextColumn::make('importe')->money('MXN', true)->label('Importe'),
                TextColumn::make('referencia')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fecha')->dateTime('d/m/Y H:i')->label('Fecha'),
                TextColumn::make('user.name')->label('Usuario')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo')->options([
                    'Ingreso' => 'Ingreso',
                    'Egreso' => 'Egreso',
                    'Ajuste' => 'Ajuste',
                ]),
                SelectFilter::make('metodo_pago')->options([
                    'Efectivo' => 'Efectivo',
                    'Transferencia' => 'Transferencia',
                    'Tarjeta' => 'Tarjeta',
                    'Cheque' => 'Cheque',
                ]),
                Filter::make('fecha')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('desde')->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn($q, $v) => $q->whereDate('fecha', '>=', $v))
                            ->when($data['hasta'] ?? null, fn($q, $v) => $q->whereDate('fecha', '<=', $v));
                    }),
            ])
            ->RecordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('fas-eye')
                    ->modalHeading('Detalle del movimiento')
                    ->form([])
                    ->disabled()
            ],RecordActionsPosition::BeforeColumns);
    }
}
