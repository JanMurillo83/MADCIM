<?php

namespace App\Filament\Resources\Productos\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class ProductosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('clave')
                    ->searchable(),
                TextColumn::make('descripcion')
                    ->searchable(),
                TextColumn::make('precio_renta_dia')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->prefix('$')->alignRight()
                    ->sortable(),
                TextColumn::make('precio_renta_semana')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->prefix('$')->alignRight()
                    ->sortable(),
                TextColumn::make('precio_renta_mes')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->prefix('$')->alignRight()
                    ->sortable(),
                TextColumn::make('precio_venta')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->prefix('$')->alignRight()
                    ->sortable(),
                TextColumn::make('m2_cubre')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('existencia')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('grupo')
                    ->searchable(),
                TextColumn::make('linea')
                    ->searchable(),
                TextColumn::make('largo')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('ancho')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                    ->modalWidth('7xl')
                    ->modalSubmitAction(function ($action) {
                        $action->icon('fas-floppy-disk');
                        $action->label('Guardar');
                        $action->extraAttributes(['style' => 'width: 150px !important;']);
                        $action->color('success');
                        return $action;
                    })->modalCancelAction(function ($action) {
                        $action->icon('fas-ban');
                        $action->label('Cancelar');
                        $action->extraAttributes(['style' => 'width: 150px !important;']);
                        $action->color('danger');
                        return $action;
                    }),
                ])
            ],RecordActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make()
                ->createAnother(false)
                ->label('Nuevo')
                ->icon('fas-circle-plus')
                ->modalWidth('7xl')
                ->modalSubmitAction(function ($action) {
                    $action->icon('fas-floppy-disk');
                    $action->label('Guardar');
                    $action->extraAttributes(['style' => 'width: 150px !important;']);
                    $action->color('success');
                    return $action;
                })->modalCancelAction(function ($action) {
                    $action->icon('fas-ban');
                    $action->label('Cancelar');
                    $action->extraAttributes(['style' => 'width: 150px !important;']);
                    $action->color('danger');
                    return $action;
                }),
            ],HeaderActionsPosition::Bottom);
    }
}
