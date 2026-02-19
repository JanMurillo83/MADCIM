<?php

namespace App\Filament\Resources\Embarques\Tables;

use App\Models\Embarque;
use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class EmbarquesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('folio')->label('Folio')->sortable(),
                TextColumn::make('fecha_programada')->dateTime('d/m/Y H:i')->label('Fecha programada'),
                TextColumn::make('vehiculo')->label('Vehículo')->searchable(),
                TextColumn::make('chofer.name')->label('Chofer')->searchable(),
                TextColumn::make('cliente.nombre')->label('Cliente')->searchable(),
                BadgeColumn::make('estatus')->colors([
                    'warning' => 'Programado',
                    'info' => 'En ruta',
                    'success' => 'Entregado',
                    'gray' => 'Parcial',
                    'danger' => 'Cancelado',
                ]),
            ])
            ->filters([])
            ->RecordActions([
                Action::make('despachar')
                    ->label('Despachar')
                    ->icon('fas-truck-fast')
                    ->visible(fn(Embarque $record) => $record->estatus === 'Programado')
                    ->requiresConfirmation()
                    ->action(function (Embarque $record, Action $action) {
                        $record->update(['estatus' => 'En ruta']);

                        \Filament\Notifications\Notification::make()
                            ->title('Embarque despachado')
                            ->success()
                            ->send();

                        // Buscar la nota de venta renta vinculada para abrir hoja de embarque en nueva pestaña
                        $item = $record->items()
                            ->where('documento_tipo', 'notas_venta_renta')
                            ->first();

                        if ($item) {
                            $url = route('notas-venta-renta.hoja-embarque', $item->documento_id);
                            $action->getLivewire()->js("window.open('{$url}', '_blank')");
                        }
                    }),
                Action::make('hoja_embarque')
                    ->label('Hoja de Embarque')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(function (Embarque $record) {
                        return $record->items()
                            ->where('documento_tipo', 'notas_venta_renta')
                            ->exists();
                    })
                    ->url(function (Embarque $record) {
                        $item = $record->items()
                            ->where('documento_tipo', 'notas_venta_renta')
                            ->first();
                        return $item ? route('notas-venta-renta.hoja-embarque', $item->documento_id) : '#';
                    })
                    ->openUrlInNewTab(),
                Action::make('entregar')
                    ->label('Entregar')
                    ->icon('fas-box-open')
                    ->visible(fn(Embarque $record) => in_array($record->estatus, ['En ruta','Parcial']))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('recibido_por')->label('Recibido por')->required(),
                        \Filament\Forms\Components\DateTimePicker::make('fecha_entrega_real')->label('Fecha/hora de entrega')->seconds(false)->default(now())->required(),
                        \Filament\Forms\Components\Textarea::make('observaciones_entrega')->label('Observaciones')->rows(3),
                    ])
                    ->action(function(Embarque $record, array $data) {
                        // Marcar como entregado (simple, sin detalle por item para esta fase)
                        $record->estatus = 'Entregado';
                        $record->save();
                        // Actualizar items existentes si los hubiera
                        foreach ($record->items as $item) {
                            $item->fill([
                                'entregado' => true,
                                'recibido_por' => $data['recibido_por'] ?? null,
                                'fecha_entrega_real' => $data['fecha_entrega_real'] ?? now(),
                                'observaciones_entrega' => $data['observaciones_entrega'] ?? null,
                            ])->save();
                        }
                        \Filament\Notifications\Notification::make()->title('Embarque entregado')->success()->send();
                    }),
            ],RecordActionsPosition::BeforeColumns);
    }
}
