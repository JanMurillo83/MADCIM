<?php

namespace App\Filament\Resources\RequisicionesCompra\Tables;

use App\Services\ComprasConversionService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class RequisicionesCompraTable
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
                        ->url(fn ($record) => route('requisiciones-compra.pdf.ticket', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('imprimir_carta')
                        ->label('Imprimir Carta')
                        ->icon('fas-file-pdf')
                        ->color('info')
                        ->url(fn ($record) => route('requisiciones-compra.pdf.carta', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('autorizar')
                        ->label('Autorizar')
                        ->icon('fas-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->estatus === 'Nueva')
                        ->action(function ($record) {
                            $record->update(['estatus' => 'Autorizada']);

                            Notification::make()
                                ->title('Requisicion autorizada')
                                ->success()
                                ->send();
                        }),
                    Action::make('convertir_oc')
                        ->label('Generar Orden de Compra')
                        ->icon('fas-arrow-right')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Generar Orden de Compra')
                        ->modalDescription('¿Deseas generar una Orden de Compra con esta requisicion?')
                        ->modalSubmitActionLabel('Si, generar')
                        ->visible(fn ($record) => $record->estatus === 'Autorizada')
                        ->action(function ($record) {
                            $service = app(ComprasConversionService::class);
                            $orden = $service->requisicionToOrdenCompra($record);

                            Notification::make()
                                ->title('Orden de compra creada')
                                ->body("Se creo la OC: {$orden->serie}-{$orden->folio}")
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
                                ->title('Requisicion cancelada')
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
