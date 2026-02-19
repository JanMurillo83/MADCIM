<?php

namespace App\Filament\Resources\NotasVentaRenta\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class NotasVentaRentaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serie')
                    ->searchable(),
                TextColumn::make('folio')
                    ->searchable(),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha_emision')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('moneda')
                    ->searchable(),
                TextColumn::make('tipo_cambio')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->label('Subtotal Partidas')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable(),
                TextColumn::make('deposito')
                    ->label('Depósito')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('impuestos_total')
                    ->label('IVA Partidas')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable(),
                TextColumn::make('saldo_pendiente')
                    ->label('Saldo Pendiente')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),
                TextColumn::make('estatus')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Activa' => 'warning',
                        'Pagada' => 'success',
                        'Cancelada' => 'danger',
                        'Borrador' => 'gray',
                        default => 'gray',
                    }),
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
                    EditAction::make(),
                    Action::make('devolucion')
                        ->label('Registrar Devolución')
                        ->icon('fas-undo')
                        ->color('success')
                        ->visible(fn ($record) => $record->estatus === 'Activa' || $record->estatus === 'Pagada')
                        ->url(fn ($record) => route('notas-venta-renta.devolucion', $record->id)),
                    Action::make('imprimir_ticket')
                        ->label('Imprimir Ticket')
                        ->icon('fas-receipt')
                        ->color('info')
                        ->url(fn ($record) => route('notas-venta-renta.pdf.ticket', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('imprimir_carta')
                        ->label('Imprimir Carta')
                        ->icon('fas-file-pdf')
                        ->color('info')
                        ->url(fn ($record) => route('notas-venta-renta.pdf.carta', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('registrar_pago')
                        ->label('Registrar pago')
                        ->icon('fas-dollar-sign')
                        ->color('success')
                        ->modalHeading(fn ($record) => "Registrar pago — {$record->serie}-{$record->folio}")
                        ->visible(fn ($record) => $record->estatus !== 'Cancelada' && $record->saldo_pendiente > 0)
                        ->form([
                            \Filament\Forms\Components\DatePicker::make('fecha_pago')->label('Fecha de pago')->default(now())->required(),
                            \Filament\Forms\Components\Select::make('metodo_pago')->label('Método de pago')->options([
                                'Efectivo' => 'Efectivo',
                                'Transferencia' => 'Transferencia',
                                'Tarjeta' => 'Tarjeta',
                                'Cheque' => 'Cheque',
                            ])->required()->live(),
                            \Filament\Forms\Components\TextInput::make('importe')->label('Importe')->numeric()->required()->default(fn($record) => (float) $record->saldo_pendiente),
                            \Filament\Forms\Components\TextInput::make('referencia')->label('Referencia')->maxLength(255),
                        ])
                        ->action(function ($record, array $data) {
                            $userId = Auth::id();
                            $cajaId = null;
                            if (($data['metodo_pago'] ?? null) === 'Efectivo') {
                                // Buscar caja abierta del usuario
                                $cajaId = \App\Models\Caja::where('estatus', 'Abierta')
                                    ->where('usuario_apertura_id', $userId)
                                    ->value('id');
                                if (!$cajaId) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Caja no disponible')
                                        ->body('No tienes una caja abierta. Abre una caja para registrar pagos en efectivo.')
                                        ->danger()->send();
                                    return;
                                }
                            }
                            \App\Models\Pagos::create([
                                'documento_tipo' => 'notas_venta_renta',
                                'documento_id' => $record->id,
                                'cliente_id' => $record->cliente_id,
                                'fecha_pago' => $data['fecha_pago'] ?? now(),
                                'forma_pago' => $data['metodo_pago'],
                                'metodo_pago' => $data['metodo_pago'],
                                'importe' => (float) $data['importe'],
                                'referencia' => $data['referencia'] ?? null,
                                'user_id' => $userId,
                                'caja_id' => $cajaId,
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Pago registrado')
                                ->body('Se registró el pago correctamente.')
                                ->success()->send();
                        }),
                    Action::make('cancelar')
                        ->label('Cancelar Nota')
                        ->icon('fas-times-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancelar Nota de Venta Renta')
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
                    })
                    ->successRedirectUrl(fn ($record) => route('notas-venta-renta.preview', $record->id)),
            ], HeaderActionsPosition::Bottom);
    }
}
