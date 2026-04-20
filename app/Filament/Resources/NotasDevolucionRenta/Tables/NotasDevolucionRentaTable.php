<?php

namespace App\Filament\Resources\NotasDevolucionRenta\Tables;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class NotasDevolucionRentaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serie')
                    ->label('Serie')
                    ->searchable(),
                TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable(),
                TextColumn::make('notaOrigen.folio')
                    ->label('Nota origen')
                    ->searchable(),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('fecha_emision')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('estatus')
                    ->badge()
                    ->color(function ($state): string {
                        return match ($state) {
                            'Pendiente' => 'warning',
                            'Parcial' => 'info',
                            'Devuelta' => 'success',
                            'Cancelada' => 'danger',
                            'Borrador', 'Aplicada' => 'gray',
                            default => 'gray',
                        };
                    }),
                TextColumn::make('items_programados')
                    ->label('Items programados')
                    ->state(fn ($record): string => number_format((float) $record->partidas->sum('cantidad_programada'), 2, '.', ',')),
                TextColumn::make('items_recogidos')
                    ->label('Items recogidos')
                    ->state(fn ($record): string => number_format((float) $record->partidas->sum('cantidad_recogida'), 2, '.', ',')),
                TextColumn::make('aplicada_en')
                    ->label('Aplicada en')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->createAnother(false),
            ], HeaderActionsPosition::Bottom)
            ->recordActions([
                ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye'),
                Action::make('ticket')
                    ->label('Ticket')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record): string => route('notas-devolucion-renta.pdf.ticket', $record->id))
                    ->openUrlInNewTab(),
                Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record): bool => !in_array($record->estatus, ['Cancelada', 'Devuelta'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar nota de devolucion')
                    ->modalDescription('La nota se marcara como cancelada. Si estaba aplicada, se revertiran las cantidades en la nota de envio.')
                    ->action(function ($record): void {
                        $record->cancelar();

                        Notification::make()
                            ->title('Nota cancelada')
                            ->body('La nota de devolucion fue cancelada correctamente.')
                            ->success()
                            ->send();
                    }),
            ], RecordActionsPosition::BeforeColumns);
    }
}
