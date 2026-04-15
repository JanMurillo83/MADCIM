<?php

namespace App\Filament\Resources\NotasDevolucionRenta\Tables;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
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
                TextColumn::make('notaEnvio.folio')
                    ->label('Nota envio')
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
                Action::make('resumen_captura')
                    ->label('Resumen/Captura')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn ($record): bool => $record->estatus !== 'Cancelada')
                    ->modalHeading(fn ($record): string => 'Resumen Nota ' . ($record->serie ?? '') . '-' . ($record->folio ?? ''))
                    ->modalDescription('Solo se permite capturar cantidades reales recibidas.')
                    ->modalWidth('7xl')
                    ->modalSubmitActionLabel('Guardar y aplicar')
                    ->form(function ($record): array {
                        $record->loadMissing(['cliente', 'notaOrigen', 'notaEnvio', 'partidas']);

                        $fields = [
                            Placeholder::make('resumen_general')
                                ->label('Datos de la nota')
                                ->content(
                                    'Cliente: ' . ($record->cliente?->nombre ?? 'N/A')
                                    . "\nNota origen: " . (($record->notaOrigen?->serie ?? '') . ($record->notaOrigen?->folio ?? 'N/A'))
                                    . "\nNota envio: " . ($record->notaEnvio?->folio ?? 'N/A')
                                    . "\nFecha: " . (optional($record->fecha_emision)->format('d/m/Y') ?? 'N/A')
                                    . "\nEstatus: " . ($record->estatus ?? 'N/A')
                                )
                                ->columnSpanFull(),
                        ];

                        foreach ($record->partidas as $partida) {
                            $fields[] = Placeholder::make('item_' . $partida->id)
                                ->label((string) ($partida->descripcion ?? 'Item'))
                                ->content('Programada: ' . number_format((float) $partida->cantidad_programada, 2, '.', ',') . ' | Aplicada: ' . number_format((float) $partida->cantidad_aplicada, 2, '.', ','))
                                ->columnSpan(8);

                            $fields[] = TextInput::make('cantidad_recogida_' . $partida->id)
                                ->label('Cantidad real recibida')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue((float) $partida->cantidad_programada)
                                ->default((float) $partida->cantidad_recogida)
                                ->required()
                                ->columnSpan(2);
                        }

                        return $fields;
                    })
                    ->action(function ($record, array $data): void {
                        $record->loadMissing('partidas');

                        foreach ($record->partidas as $partida) {
                            $field = 'cantidad_recogida_' . $partida->id;
                            if (!array_key_exists($field, $data)) {
                                continue;
                            }

                            $cantidad = max(0, (float) $data[$field]);
                            $partida->update([
                                'cantidad_recogida' => $cantidad,
                            ]);
                        }

                        $record->aplicarCantidadesRecogidas();

                        Notification::make()
                            ->title('Cantidades aplicadas')
                            ->body('Se guardaron las cantidades reales recibidas y se actualizo la nota de envio.')
                            ->success()
                            ->send();
                    }),
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
