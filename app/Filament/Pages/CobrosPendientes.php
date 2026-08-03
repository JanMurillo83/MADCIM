<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Services\CobroNotaService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class CobrosPendientes extends Page implements HasActions
{
    use HasRolePageAccess;
    use InteractsWithActions;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Cobros pendientes';
    protected static ?string $title = 'Notas pendientes de pago';
    protected static string|null|\UnitEnum $navigationGroup = 'Caja';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.cobros-pendientes';

    public string $tipoSeleccionado = '';
    public ?int $notaSeleccionada = null;

    public function abrirPago(string $tipo, int $id): void
    {
        $this->tipoSeleccionado = $tipo;
        $this->notaSeleccionada = $id;
        $this->mountAction('pagar');
    }

    #[Computed]
    public function notasPendientes(): Collection
    {
        $rentas = NotasVentaRenta::query()
            ->with('cliente')
            ->where('estatus', '!=', 'Cancelada')
            ->where('saldo_pendiente', '>', 0)
            ->get()
            ->map(fn (NotasVentaRenta $nota): array => [
                'tipo' => 'notas_venta_renta',
                'tipo_label' => 'Renta',
                'id' => $nota->id,
                'folio' => trim(($nota->serie ?? '') . '-' . ($nota->folio ?? '')),
                'fecha' => optional($nota->fecha_emision)->format('d/m/Y'),
                'cliente' => $nota->cliente?->nombre ?? 'N/A',
                'total' => (float) $nota->total,
                'saldo' => (float) $nota->saldo_pendiente,
            ]);

        $ventas = NotasVentaVenta::query()
            ->with('cliente')
            ->where('estatus', '!=', 'Cancelada')
            ->where('saldo_pendiente', '>', 0)
            ->get()
            ->map(fn (NotasVentaVenta $nota): array => [
                'tipo' => 'notas_venta_venta',
                'tipo_label' => 'Venta',
                'id' => $nota->id,
                'folio' => trim(($nota->serie ?? '') . '-' . ($nota->folio ?? '')),
                'fecha' => optional($nota->fecha_emision)->format('d/m/Y'),
                'cliente' => $nota->cliente?->nombre ?? 'N/A',
                'total' => (float) $nota->total,
                'saldo' => (float) $nota->saldo_pendiente,
            ]);

        return $rentas->concat($ventas)->sortByDesc('fecha')->values();
    }

    public function saldoSeleccionado(): float
    {
        $nota = $this->notasPendientes->first(
            fn (array $nota): bool => $nota['tipo'] === $this->tipoSeleccionado && $nota['id'] === $this->notaSeleccionada,
        );

        return (float) ($nota['saldo'] ?? 0);
    }

    public function pagarAction(): Action
    {
        return Action::make('pagar')
            ->label('Registrar pago')
            ->modalHeading('Registrar pago')
            ->modalSubmitActionLabel('Guardar e imprimir ticket')
            ->modalWidth('lg')
            ->form([
                Section::make('Datos del pago')->schema([
                    DatePicker::make('fecha_pago')
                        ->label('Fecha de pago')
                        ->default(now())
                        ->required(),
                    TextInput::make('importe')
                        ->label('Importe a cobrar')
                        ->prefix('$')
                        ->numeric()
                        ->readOnly()
                        ->default(fn (): float => $this->saldoSeleccionado()),
                    Select::make('forma_pago')
                        ->label('Forma de pago')
                        ->options([
                            '01' => '01 - Efectivo',
                            '02' => '02 - Cheque nominativo',
                            '03' => '03 - Transferencia electrónica',
                            '04' => '04 - Tarjeta de crédito',
                            '28' => '28 - Tarjeta de débito',
                        ])
                        ->default('01')
                        ->live()
                        ->required(),
                    TextInput::make('importe_recibido')
                        ->label('Efectivo recibido')
                        ->prefix('$')
                        ->numeric()
                        ->minValue(0)
                        ->default(fn (): float => $this->saldoSeleccionado())
                        ->visible(fn (Get $get): bool => $get('forma_pago') === '01')
                        ->required(fn (Get $get): bool => $get('forma_pago') === '01')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set): void {
                            $recibido = (float) ($get('importe_recibido') ?? 0);
                            $set('cambio', max(0, round($recibido - $this->saldoSeleccionado(), 2)));
                        }),
                    TextInput::make('cambio')
                        ->label('Cambio')
                        ->prefix('$')
                        ->numeric()
                        ->readOnly()
                        ->default(0),
                    TextInput::make('referencia')
                        ->label('Referencia')
                        ->maxLength(255),
                    Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),
            ])
            ->action(function (array $data): void {
                try {
                    $pago = app(CobroNotaService::class)->cobrar(
                        $this->tipoSeleccionado,
                        (int) $this->notaSeleccionada,
                        $data,
                        (int) Auth::id(),
                    );

                    $this->dispatch('abrir-ticket-pago', url: route('pagos.ticket', $pago->id));
                    Notification::make()
                        ->title('Pago registrado')
                        ->body('El ticket se abrirá en una nueva pestaña.')
                        ->success()
                        ->send();
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->title('No se pudo registrar el pago')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
