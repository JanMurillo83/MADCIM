<?php

namespace App\Filament\Resources\Pagos\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('fecha_pago')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('documento_tipo')
                    ->label('Tipo Doc.')
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'notas_venta_renta' => 'NV Renta',
                            'notas_venta_venta' => 'NV Venta',
                            'facturas_cfdi' => 'Factura',
                            default => $state
                        };
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('folio_documento')
                    ->label('Folio Doc.')
                    ->getStateUsing(function ($record) {
                        $documento = null;
                        switch ($record->documento_tipo) {
                            case 'notas_venta_renta':
                                $documento = \App\Models\NotasVentaRenta::find($record->documento_id);
                                break;
                            case 'notas_venta_venta':
                                $documento = \App\Models\NotasVentaVenta::find($record->documento_id);
                                break;
                            case 'facturas_cfdi':
                                $documento = \App\Models\FacturasCfdi::find($record->documento_id);
                                break;
                        }
                        return $documento ? $documento->folio : 'N/A';
                    }),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('forma_pago')
                    ->label('Forma de Pago')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('importe')
                    ->label('Importe')
                    ->money('MXN')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('documento_tipo')
                    ->label('Tipo de documento')
                    ->options([
                        'notas_venta_renta' => 'Nota de Venta (Renta)',
                        'notas_venta_venta' => 'Nota de Venta (Venta)',
                        'facturas_cfdi' => 'Factura CFDI',
                    ]),
                Tables\Filters\SelectFilter::make('forma_pago')
                    ->label('Forma de pago')
                    ->options([
                        'Efectivo' => 'Efectivo',
                        'Tarjeta Débito' => 'Tarjeta Débito',
                        'Tarjeta Crédito' => 'Tarjeta Crédito',
                        'Transferencia' => 'Transferencia',
                        'Cheque' => 'Cheque',
                        'Otro' => 'Otro',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('fecha_pago', 'desc');
    }
}
