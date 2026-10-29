<?php

namespace App\Filament\Resources\Cotizaciones\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CotizacionesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serie')
                    ->searchable(),
                TextColumn::make('folio')
                    ->searchable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable()
                    ->searchable()
                    ->visible(fn (): bool => Auth::user()?->isAdmin() ?? false),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo_cotizacion')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'renta' ? 'Renta' : 'Venta'),
                TextColumn::make('fecha_emision')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('moneda')
                    ->searchable(),
                TextColumn::make('tipo_cambio')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable(),
                TextColumn::make('total')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable(),
                TextColumn::make('estatus')
                    ->searchable(),
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
                    ->label('Cotizacion origen')
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
                Action::make('imprimir_ticket')
                    ->label('Imprimir Ticket')
                    ->icon('fas-receipt')
                    ->color('info')
                    ->url(fn ($record) => route('cotizaciones.pdf.ticket', $record->id))
                    ->openUrlInNewTab(),
                Action::make('imprimir_carta')
                    ->label('Imprimir Carta')
                    ->icon('fas-file-pdf')
                    ->color('info')
                    ->url(fn ($record) => route('cotizaciones.pdf.carta', $record->id))
                    ->openUrlInNewTab(),
                Action::make('convertir')
                    ->label(fn ($record) => $record->tipo_cotizacion === 'renta' ? 'Capturar Nota de Renta' : 'Capturar Nota de Venta')
                    ->icon('fas-arrow-right')
                    ->color('success')
                    ->url(fn ($record) => route('cotizaciones.convertir', $record))
                    ->visible(fn ($record) => $record->estatus === 'Activa'),
                Action::make('cancelar')
                    ->label('Cancelar Cotización')
                    ->icon('fas-times-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar Cotización')
                    ->modalDescription('¿Estás seguro de que deseas cancelar esta cotización? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, cancelar')
                    ->visible(fn ($record) => $record->estatus === 'Activa')
                    ->action(function ($record) {
                        $record->update(['estatus' => 'Cancelada']);

                        Notification::make()
                            ->title('Cotización cancelada')
                            ->body("La cotización {$record->serie}-{$record->folio} ha sido cancelada exitosamente.")
                            ->success()
                            ->send();
                    }),
                ])
            ], RecordActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo')
                    ->icon('fas-circle-plus'),
            ], HeaderActionsPosition::Bottom);
    }
}
