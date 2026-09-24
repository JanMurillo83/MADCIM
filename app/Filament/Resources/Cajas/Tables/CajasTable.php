<?php

namespace App\Filament\Resources\Cajas\Tables;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Services\CajaArqueoService;
use Filament\Actions\Action;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

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
                        \Filament\Forms\Components\Placeholder::make('desglose_ingresos')
                            ->label('Desglose de ingresos por forma de pago')
                            ->content(fn (Caja $record): string => self::desgloseMovimientos($record, 'Ingreso')),
                        \Filament\Forms\Components\Placeholder::make('desglose_egresos')
                            ->label('Desglose de egresos por forma de pago')
                            ->content(fn (Caja $record): string => self::desgloseMovimientos($record, 'Egreso')),
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
                    ->modalWidth('6xl')
                    ->visible(fn (Caja $record) => $record->estatus === 'Abierta')
                    ->requiresConfirmation()
                    ->form(fn (Caja $record): array => self::formularioCierre($record))
                    ->action(function (Caja $record, array $data, \Livewire\Component $livewire): void {
                        $resultado = app(CajaArqueoService::class)->cerrar(
                            $record,
                            $data['denominaciones'] ?? [],
                            $data['observaciones_cierre'] ?? null,
                            Auth::id(),
                        );

                        $livewire->js("window.open('" . route('cajas.cierre.ticket', $record->id) . "', '_blank')");
                        \Filament\Notifications\Notification::make()
                            ->title('Caja cerrada')
                            ->body('Efectivo contado: $'.number_format((float)$resultado['efectivo_contado'], 2).' · Diferencia: $'.number_format((float)$resultado['diferencia'], 2))
                            ->success()->send();
                    }),
            ],RecordActionsPosition::BeforeColumns)
            ->headerActions([
                //CreateAction::make()
            ],HeaderActionsPosition::Bottom);
    }

    private static function formularioCierre(Caja $caja): array
    {
        $service = app(CajaArqueoService::class);
        $resumen = $service->resumen($caja);
        $denominaciones = $service->denominaciones();

        $camposDenominaciones = static function (array $grupo): array {
            return collect($grupo)->map(function (float $valor, string $clave) {
                return \Filament\Forms\Components\TextInput::make('denominaciones.' . $clave)
                    ->label('$' . number_format($valor, 2))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->live(onBlur: true);
            })->values()->all();
        };

        return [
            Tabs::make('cierre')
                ->tabs([
                    Tab::make('Generales')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Resumen del día')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('saldo_inicial')->label('Saldo inicial')->content('$' . number_format($resumen['saldo_inicial'], 2)),
                                    \Filament\Forms\Components\Placeholder::make('ingresos_efectivo')->label('Ingresos efectivo')->content('$' . number_format($resumen['ingresos_efectivo'], 2)),
                                    \Filament\Forms\Components\Placeholder::make('egresos_efectivo')->label('Egresos efectivo')->content('$' . number_format($resumen['egresos_efectivo'], 2)),
                                    \Filament\Forms\Components\Placeholder::make('efectivo_teorico')->label('Efectivo teórico')->content('$' . number_format($resumen['efectivo_teorico'], 2)),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                            \Filament\Schemas\Components\Section::make('Desglose de formas de pago')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('desglose')
                                        ->hiddenLabel()
                                        ->content(fn (): HtmlString => self::tablaDesglose($resumen['desglose'])),
                                ])
                                ->columnSpanFull(),
                            \Filament\Forms\Components\Textarea::make('observaciones_cierre')
                                ->label('Observaciones de cierre')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Tab::make('Desglose de moneda')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Monedas')
                                ->schema($camposDenominaciones($denominaciones['monedas']))
                                ->columns(3)
                                ->columnSpanFull(),
                            \Filament\Schemas\Components\Section::make('Billetes')
                                ->schema($camposDenominaciones($denominaciones['billetes']))
                                ->columns(3)
                                ->columnSpanFull(),
                            \Filament\Schemas\Components\Section::make('Resultado del conteo')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('efectivo_contado')
                                        ->label('Efectivo contado')
                                        ->content(function (\Filament\Schemas\Components\Utilities\Get $get) use ($service): string {
                                            return '$' . number_format($service->efectivoContado($get('denominaciones') ?? []), 2);
                                        }),
                                    \Filament\Forms\Components\Placeholder::make('diferencia')
                                        ->label('Diferencia contra efectivo teórico')
                                        ->content(function (\Filament\Schemas\Components\Utilities\Get $get) use ($service, $resumen): string {
                                            $diferencia = $service->efectivoContado($get('denominaciones') ?? []) - $resumen['efectivo_teorico'];
                                            return '$' . number_format($diferencia, 2);
                                        }),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->columnSpanFull(),
        ];
    }

    private static function desgloseMovimientos(Caja $caja, string $tipo): string
    {
        $desglose = $caja->movimientos()
            ->where('tipo', $tipo)
            ->get()
            ->groupBy(fn (CajaMovimiento $movimiento): string => $movimiento->metodo_pago ?: 'Sin especificar')
            ->map(fn ($movimientos): float => (float) $movimientos->sum('importe'));

        if ($desglose->isEmpty()) {
            return 'Sin movimientos.';
        }

        return $desglose
            ->map(fn (float $importe, string $metodo): string => $metodo . ': MXN $' . number_format($importe, 2))
            ->implode(' | ');
    }

    /** @param array<string, array{ingresos: float, egresos: float}> $desglose */
    private static function tablaDesglose(array $desglose): HtmlString
    {
        if ($desglose === []) {
            return new HtmlString('<p class="text-sm text-gray-500">Sin movimientos registrados.</p>');
        }

        $filas = collect($desglose)
            ->map(function (array $totales, string $metodo): string {
                $metodo = e($metodo);
                $ingresos = '$' . number_format($totales['ingresos'], 2);
                $egresos = '$' . number_format($totales['egresos'], 2);

                return '<tr class="border-t border-gray-200 dark:border-gray-700">'
                    . '<td class="px-3 py-2 font-medium text-gray-950 dark:text-white">' . $metodo . '</td>'
                    . '<td class="px-3 py-2 text-right tabular-nums">' . $ingresos . '</td>'
                    . '<td class="px-3 py-2 text-right tabular-nums">' . $egresos . '</td>'
                    . '</tr>';
            })
            ->implode('');

        return new HtmlString(
            '<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">'
            . '<table class="w-full text-sm">'
            . '<thead class="bg-gray-50 dark:bg-gray-800">'
            . '<tr>'
            . '<th class="px-3 py-2 text-left font-medium">Forma de pago</th>'
            . '<th class="px-3 py-2 text-right font-medium">Ingresos</th>'
            . '<th class="px-3 py-2 text-right font-medium">Egresos</th>'
            . '</tr>'
            . '</thead><tbody>' . $filas . '</tbody></table></div>'
        );
    }
}
