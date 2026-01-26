<?php

namespace App\Filament\Resources\Documentos\Tables;

use App\Enums\TipoDocumento;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo')
                    ->formatStateUsing(fn (?string $state): string => TipoDocumento::labelFor($state))
                    ->searchable(),
                TextColumn::make('serie')
                    ->searchable(),
                TextColumn::make('folio')
                    ->searchable(),
                TextColumn::make('fecha_emision')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('moneda')
                    ->searchable(),
                TextColumn::make('tipo_cambio')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('impuestos_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estatus')
                    ->searchable(),
                TextColumn::make('uso_cfdi')
                    ->searchable(),
                TextColumn::make('forma_pago')
                    ->searchable(),
                TextColumn::make('metodo_pago')
                    ->searchable(),
                TextColumn::make('regimen_fiscal_receptor')
                    ->searchable(),
                TextColumn::make('rfc_emisor')
                    ->searchable(),
                TextColumn::make('rfc_receptor')
                    ->searchable(),
                TextColumn::make('razon_social_receptor')
                    ->searchable(),
                TextColumn::make('cfdi_uuid')
                    ->searchable(),
                TextColumn::make('documentoOrigen.folio')
                    ->label('Documento origen')
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
            ->filters([
                SelectFilter::make('tipo')
                    ->options(TipoDocumento::options()),
            ])
            ->recordActions([
                EditAction::make(),
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
