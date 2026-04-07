<?php

namespace App\Filament\Resources\FacturasCfdi\Tables;

use App\Models\FacturaCfdiPartidas;
use App\Models\FacturasCfdi;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Pagos;
use App\Models\Productos;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class FacturasCfdiTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
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
                EditAction::make(),
                Action::make('importar_notas')
                    ->label('Importar notas')
                    ->icon('fas-file-import')
                    ->color('info')
                    ->visible(fn (FacturasCfdi $record): bool => (int) ($record->cliente_id ?? 0) > 0)
                    ->modalHeading(fn (FacturasCfdi $record): string => "Importar notas - {$record->serie}{$record->folio}")
                    ->modalDescription('Selecciona una o varias notas de venta/renta del mismo cliente para agregar sus partidas a la factura.')
                    ->modalWidth('5xl')
                    ->form([
                        Select::make('notas_venta_ids')
                            ->label('Notas de venta')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function (FacturasCfdi $record) {
                                if (!$record->cliente_id) {
                                    return [];
                                }

                                return NotasVentaVenta::query()
                                    ->where('cliente_id', $record->cliente_id)
                                    ->where('estatus', '!=', 'Cancelada')
                                    ->orderByDesc('fecha_emision')
                                    ->get()
                                    ->mapWithKeys(function (NotasVentaVenta $nota) {
                                        $label = trim(($nota->serie ?? '') . ($nota->folio ?? ''));
                                        $label .= ' | ' . optional($nota->fecha_emision)->format('Y-m-d');
                                        $label .= ' | $' . number_format((float) $nota->total, 2);

                                        return [$nota->id => $label];
                                    })
                                    ->all();
                            }),
                        Select::make('notas_renta_ids')
                            ->label('Notas de renta')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function (FacturasCfdi $record) {
                                if (!$record->cliente_id) {
                                    return [];
                                }

                                return NotasVentaRenta::query()
                                    ->where('cliente_id', $record->cliente_id)
                                    ->where('estatus', '!=', 'Cancelada')
                                    ->orderByDesc('fecha_emision')
                                    ->get()
                                    ->mapWithKeys(function (NotasVentaRenta $nota) {
                                        $label = trim(($nota->serie ?? '') . ($nota->folio ?? ''));
                                        $label .= ' | ' . optional($nota->fecha_emision)->format('Y-m-d');
                                        $label .= ' | $' . number_format((float) $nota->total, 2);

                                        return [$nota->id => $label];
                                    })
                                    ->all();
                            }),
                    ])
                    ->action(function (FacturasCfdi $record, array $data): void {
                        if (!$record->cliente_id) {
                            Notification::make()
                                ->title('No se puede importar')
                                ->body('La factura no tiene cliente asignado.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $notasVentaIds = array_values(array_filter(array_map('intval', (array) ($data['notas_venta_ids'] ?? []))));
                        $notasRentaIds = array_values(array_filter(array_map('intval', (array) ($data['notas_renta_ids'] ?? []))));

                        if (empty($notasVentaIds) && empty($notasRentaIds)) {
                            Notification::make()
                                ->title('Sin selección')
                                ->body('Selecciona al menos una nota de venta o de renta.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $insertadas = 0;
                        $omitidas = 0;

                        DB::transaction(function () use ($record, $notasVentaIds, $notasRentaIds, &$insertadas, &$omitidas) {
                            $productosCache = [];
                            $resolverProducto = function (mixed $item) use (&$productosCache): ?Productos {
                                if ($item === null || $item === '') {
                                    return null;
                                }

                                $key = (string) $item;

                                if (!array_key_exists($key, $productosCache)) {
                                    $productosCache[$key] = Productos::find($item);
                                }

                                return $productosCache[$key];
                            };

                            $existentes = FacturaCfdiPartidas::query()
                                ->where('factura_cfdi_id', $record->id)
                                ->whereNotNull('no_identificacion')
                                ->pluck('no_identificacion')
                                ->all();

                            $identificadoresExistentes = array_fill_keys($existentes, true);

                            $notasVenta = NotasVentaVenta::query()
                                ->with('partidas')
                                ->where('cliente_id', $record->cliente_id)
                                ->where('estatus', '!=', 'Cancelada')
                                ->whereIn('id', $notasVentaIds)
                                ->get();

                            foreach ($notasVenta as $nota) {
                                foreach ($nota->partidas as $partida) {
                                    $identificador = "NVV:{$nota->id}:{$partida->id}";

                                    if (isset($identificadoresExistentes[$identificador])) {
                                        $omitidas++;
                                        continue;
                                    }

                                    $producto = $resolverProducto($partida->item);

                                    FacturaCfdiPartidas::create([
                                        'factura_cfdi_id' => $record->id,
                                        'cantidad' => (float) $partida->cantidad,
                                        'item' => (string) $partida->item,
                                        'clave_prod_serv' => $producto?->clave_prod_serv,
                                        'no_identificacion' => $identificador,
                                        'clave_unidad' => $producto?->clave_unidad,
                                        'unidad' => $producto?->unidad_sat,
                                        'descripcion' => $partida->descripcion,
                                        'objeto_imp' => $producto?->objeto_imp,
                                        'valor_unitario' => (float) $partida->valor_unitario,
                                        'subtotal' => (float) $partida->subtotal,
                                        'descuento' => 0,
                                        'impuestos' => (float) $partida->impuestos,
                                        'total' => (float) $partida->total,
                                    ]);

                                    $identificadoresExistentes[$identificador] = true;
                                    $insertadas++;
                                }
                            }

                            $notasRenta = NotasVentaRenta::query()
                                ->with('partidas')
                                ->where('cliente_id', $record->cliente_id)
                                ->where('estatus', '!=', 'Cancelada')
                                ->whereIn('id', $notasRentaIds)
                                ->get();

                            foreach ($notasRenta as $nota) {
                                foreach ($nota->partidas as $partida) {
                                    $identificador = "NVR:{$nota->id}:{$partida->id}";

                                    if (isset($identificadoresExistentes[$identificador])) {
                                        $omitidas++;
                                        continue;
                                    }

                                    $producto = $resolverProducto($partida->item);

                                    FacturaCfdiPartidas::create([
                                        'factura_cfdi_id' => $record->id,
                                        'cantidad' => (float) $partida->cantidad,
                                        'item' => (string) $partida->item,
                                        'clave_prod_serv' => $producto?->clave_prod_serv,
                                        'no_identificacion' => $identificador,
                                        'clave_unidad' => $producto?->clave_unidad,
                                        'unidad' => $producto?->unidad_sat,
                                        'descripcion' => $partida->descripcion,
                                        'objeto_imp' => $producto?->objeto_imp,
                                        'valor_unitario' => (float) $partida->valor_unitario,
                                        'subtotal' => (float) $partida->subtotal,
                                        'descuento' => 0,
                                        'impuestos' => (float) $partida->impuestos,
                                        'total' => (float) $partida->total,
                                    ]);

                                    $identificadoresExistentes[$identificador] = true;
                                    $insertadas++;
                                }
                            }

                            $subtotal = (float) $record->partidas()->sum('subtotal');
                            $impuestos = (float) $record->partidas()->sum('impuestos');
                            $total = (float) $record->partidas()->sum('total');
                            $pagado = (float) Pagos::query()
                                ->where('documento_tipo', 'facturas_cfdi')
                                ->where('documento_id', $record->id)
                                ->sum('importe');

                            $record->update([
                                'subtotal' => $subtotal,
                                'impuestos_total' => $impuestos,
                                'total' => $total,
                                'saldo_pendiente' => max(0, $total - $pagado),
                            ]);
                        });

                        Notification::make()
                            ->title('Notas importadas')
                            ->body("Partidas agregadas: {$insertadas}. Omitidas por duplicado: {$omitidas}.")
                            ->success()
                            ->send();
                    }),
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
                    }),
            ], HeaderActionsPosition::Bottom);
    }
}
