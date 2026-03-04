<?php

namespace App\Filament\Resources\NotasVentaVenta\Tables;

use App\Models\NotasVentaVenta;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class NotasVentaVentaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serie')
                ->label('Nota Origen')->getStateUsing(fn ($record) => $record->serie.$record->folio),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha_emision')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('total')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable(),
                TextColumn::make('saldo_pendiente')
                    ->label('Saldo Pendiente')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),
                TextColumn::make('estatus_pago')
                    ->label('Estatus Pago')
                    ->badge()
                    ->getStateUsing(function (NotasVentaVenta $record) {
                        if ($record->estatus === 'Cancelada') return 'Cancelada';
                        if ((float)$record->saldo_pendiente <= 0) return 'Pagada';
                        return 'Crédito';
                    })
                    ->colors([
                        'success' => 'Pagada',
                        'warning' => 'Crédito',
                        'danger' => 'Cancelada',
                    ]),
                TextColumn::make('estatus_envio')
                    ->label('Estatus Envío')
                    ->badge()
                    ->colors([
                        'danger' => 'Pendiente de Envío',
                        'success' => 'Enviada',
                    ]),
                TextColumn::make('uso_cfdi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('forma_pago')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('metodo_pago')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('regimen_fiscal_receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rfc_emisor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rfc_receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('razon_social_receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cfdi_uuid')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('documentoOrigen.folio')
                    ->label('Documento origen')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    EditAction::make()
                        ->visible(fn (NotasVentaVenta $record) => $record->estatus !== 'Cancelada'),
                    Action::make('imprimir_ticket')
                        ->label('Imprimir Ticket')
                        ->icon('fas-receipt')
                        ->color('info')
                        ->url(fn ($record) => route('notas-venta-venta.pdf.ticket', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('imprimir_carta')
                        ->label('Imprimir Carta')
                        ->icon('fas-file-pdf')
                        ->color('info')
                        ->url(fn ($record) => route('notas-venta-venta.pdf.carta', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('marcar_enviada')
                        ->label('Marcar Enviada')
                        ->icon('heroicon-o-truck')
                        ->color('success')
                        ->visible(fn (NotasVentaVenta $record) => ($record->estatus_envio ?? 'Pendiente de Envío') === 'Pendiente de Envío' && $record->estatus !== 'Cancelada')
                        ->requiresConfirmation()
                        ->modalHeading(fn (NotasVentaVenta $record) => 'Marcar Enviada - Nota ' . $record->serie . $record->folio)
                        ->modalDescription('¿Confirma que esta nota ha sido enviada?')
                        ->modalSubmitActionLabel('Confirmar')
                        ->action(function (NotasVentaVenta $record) {
                            $record->update(['estatus_envio' => 'Enviada']);
                            Notification::make()
                                ->title('Estatus actualizado')
                                ->body('La nota ' . $record->serie . $record->folio . ' ha sido marcada como Enviada.')
                                ->success()
                                ->send();
                        }),
                    Action::make('cancelar')
                        ->label('Cancelar Nota')
                        ->icon('fas-times-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancelar Nota de Venta Venta')
                        ->modalDescription('¿Estás seguro de que deseas cancelar esta nota de venta? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, cancelar')
                        ->visible(fn ($record) => $record->estatus === 'Activa')
                        ->action(function ($record) {
                            $record->update(['estatus' => 'Cancelada']);

                            Notification::make()
                                ->title('Nota cancelada')
                                ->body("La nota {$record->serie}-{$record->folio} ha sido cancelada exitosamente.")
                                ->success()
                                ->send();
                        }),
                ])
            ], RecordActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make()
                    ->createAnother(false)
                    ->label('Nuevo')
                    ->icon('fas-circle-plus')
                    ->modalWidth('full')
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
                    })->after(function ($record) {
                        $record->update([
                            'estatus' => 'Activa',
                            'saldo_pendiente' => $record->total
                        ]);
                    }),
            ], HeaderActionsPosition::Bottom);
    }
}
