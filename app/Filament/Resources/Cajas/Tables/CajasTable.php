<?php

namespace App\Filament\Resources\Cajas\Tables;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CajasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('nombre')->searchable(),
                TextColumn::make('estatus')->badge(),
                TextColumn::make('saldo_inicial_cash')->money('MXN', true),
                TextColumn::make('total_ingresos_cash')->money('MXN', true),
                TextColumn::make('total_egresos_cash')->money('MXN', true),
                TextColumn::make('fecha_apertura')->dateTime('d/m/Y H:i'),
                TextColumn::make('fecha_cierre')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                // add filters later
            ])
            ->recordActions([
                Action::make('abrir')
                    ->label('Abrir')
                    ->icon('fas-door-open')
                    ->visible(fn (Caja $record) => $record->estatus !== 'Abierta')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('saldo_inicial_cash')
                            ->label('Saldo inicial')
                            ->numeric()
                            ->default(fn (Caja $record) => $record->saldo_inicial_cash ?? 0)
                            ->prefix('MXN $')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Caja $record, array $data) {
                        $userId = Auth::id();
                        // Validar que el usuario no tenga otra caja abierta
                        $existe = Caja::where('estatus', 'Abierta')
                            ->where('usuario_apertura_id', $userId)
                            ->where('id', '!=', $record->id)
                            ->exists();
                        if ($existe) {
                            \Filament\Notifications\Notification::make()
                                ->title('No permitido')
                                ->body('Ya tienes una caja abierta. Debes cerrarla antes de abrir otra.')
                                ->danger()->send();
                            return;
                        }
                        $record->saldo_inicial_cash = $data['saldo_inicial_cash'];
                        $record->estatus = 'Abierta';
                        $record->fecha_apertura = now();
                        $record->usuario_apertura_id = $userId;
                        $record->save();
                        \Filament\Notifications\Notification::make()
                            ->title('Caja abierta')
                            ->success()->send();
                    }),
                Action::make('arqueo')
                    ->label('Arqueo')
                    ->icon('fas-scale-balanced')
                    ->modalHeading('Arqueo de Caja')
                    ->modalWidth('lg')
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('saldo_inicial')->label('Saldo inicial')
                            ->content(fn (Caja $record) => 'MXN $'.number_format((float)($record->saldo_inicial_cash ?? 0), 2)),
                        \Filament\Forms\Components\Placeholder::make('ingresos')->label('Ingresos (efectivo)')
                            ->content(function (Caja $record) {
                                $ing = $record->movimientos()
                                    ->where('tipo', 'Ingreso')
                                    ->where('metodo_pago', 'Efectivo')
                                    ->sum('importe');
                                return 'MXN $'.number_format((float)$ing, 2);
                            }),
                        \Filament\Forms\Components\Placeholder::make('egresos')->label('Egresos (efectivo)')
                            ->content(function (Caja $record) {
                                $eg = $record->movimientos()
                                    ->where('tipo', 'Egreso')
                                    ->where('metodo_pago', 'Efectivo')
                                    ->sum('importe');
                                return 'MXN $'.number_format((float)$eg, 2);
                            }),
                        \Filament\Forms\Components\Placeholder::make('saldo')->label('Saldo teórico en caja')
                            ->content(function (Caja $record) {
                                $ing = $record->movimientos()->where('tipo', 'Ingreso')->where('metodo_pago', 'Efectivo')->sum('importe');
                                $eg = $record->movimientos()->where('tipo', 'Egreso')->where('metodo_pago', 'Efectivo')->sum('importe');
                                $saldo = (float)($record->saldo_inicial_cash ?? 0) + (float)$ing - (float)$eg;
                                return 'MXN $'.number_format($saldo, 2);
                            }),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Action::make('cerrar')
                    ->label('Cerrar')
                    ->icon('fas-door-closed')
                    ->color('danger')
                    ->visible(fn (Caja $record) => $record->estatus === 'Abierta')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Textarea::make('observaciones_cierre')->label('Observaciones de cierre'),
                    ])
                    ->action(function (Caja $record, array $data) {
                        // Calcular totales
                        $ing = $record->movimientos()->where('tipo', 'Ingreso')->where('metodo_pago', 'Efectivo')->sum('importe');
                        $eg = $record->movimientos()->where('tipo', 'Egreso')->where('metodo_pago', 'Efectivo')->sum('importe');
                        $record->total_ingresos_cash = $ing;
                        $record->total_egresos_cash = $eg;
                        // diferencia contra saldo teórico (no realizamos conteo físico aquí)
                        $saldoTeorico = (float)($record->saldo_inicial_cash ?? 0) + (float)$ing - (float)$eg;
                        $record->total_diferencia = 0; // placeholder hasta que exista conteo físico
                        $record->observaciones_cierre = $data['observaciones_cierre'] ?? null;
                        $record->estatus = 'Cerrada';
                        $record->fecha_cierre = now();
                        $record->usuario_cierre_id = Auth::id();
                        $record->save();
                        \Filament\Notifications\Notification::make()
                            ->title('Caja cerrada')
                            ->body('Ingresos: $'.number_format((float)$ing,2).' · Egresos: $'.number_format((float)$eg,2).' · Saldo: $'.number_format((float)$saldoTeorico,2))
                            ->success()->send();
                    }),
            ],RecordActionsPosition::BeforeColumns)
            ->headerActions([
                //CreateAction::make()
            ],HeaderActionsPosition::Bottom);
    }
}
