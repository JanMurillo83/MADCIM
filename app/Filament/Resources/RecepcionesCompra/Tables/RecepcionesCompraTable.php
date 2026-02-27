<?php

namespace App\Filament\Resources\RecepcionesCompra\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class RecepcionesCompraTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serie')
                    ->searchable(),
                TextColumn::make('folio')
                    ->searchable(),
                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha_emision')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable(),
                TextColumn::make('impuestos_total')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable(),
                TextColumn::make('total')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable(),
                TextColumn::make('estatus')
                    ->searchable(),
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
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('imprimir_ticket')
                        ->label('Imprimir Ticket')
                        ->icon('fas-receipt')
                        ->color('info')
                        ->url(fn ($record) => route('recepciones-compra.pdf.ticket', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('imprimir_carta')
                        ->label('Imprimir Carta')
                        ->icon('fas-file-pdf')
                        ->color('info')
                        ->url(fn ($record) => route('recepciones-compra.pdf.carta', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('cerrar')
                        ->label('Cerrar')
                        ->icon('fas-lock')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->estatus === 'Nueva')
                        ->action(function ($record) {
                            $record->update(['estatus' => 'Cerrada']);

                            Notification::make()
                                ->title('Recepcion cerrada')
                                ->success()
                                ->send();
                        }),
                    Action::make('cancelar')
                        ->label('Cancelar')
                        ->icon('fas-times-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->estatus !== 'Cancelada')
                        ->action(function ($record) {
                            $record->update(['estatus' => 'Cancelada']);

                            Notification::make()
                                ->title('Recepcion cancelada')
                                ->success()
                                ->send();
                        }),
                ]),
            ], RecordActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo')
                    ->icon('fas-circle-plus'),
            ], HeaderActionsPosition::Bottom);
    }
}
