<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Models\CierreDevolucionRenta;
use App\Models\CajaMovimiento;
use App\Models\DevolucionesRenta;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Services\CobroNotaService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    public string $pestana = 'hoy';

    public ?int $cierreDepositoSeleccionado = null;

    public ?string $tipoDepositoSeleccionado = null;

    public string $folioIne = '';

    public function abrirPago(string $tipo, int $id): void
    {
        $this->tipoSeleccionado = $tipo;
        $this->notaSeleccionada = $id;
        $this->mountAction('pagar');
    }

    public function abrirConfirmacionDeposito(string $tipo, int $id): void
    {
        if ($tipo === 'cierre') {
            CierreDevolucionRenta::query()->where('estatus', 'PendienteCaja')->findOrFail($id);
        } elseif ($tipo === 'movimiento') {
            CajaMovimiento::query()->where('tipo', 'Egreso')->where('fuente', 'Devolución depósito renta')->where('aplicado_a_caja', false)->findOrFail($id);
        } else {
            abort(404);
        }

        $this->tipoDepositoSeleccionado = $tipo;
        $this->cierreDepositoSeleccionado = $id;
        $this->folioIne = '';
        $this->resetValidation('folioIne');
    }

    public function cancelarConfirmacionDeposito(): void
    {
        $this->cierreDepositoSeleccionado = null;
        $this->tipoDepositoSeleccionado = null;
        $this->folioIne = '';
        $this->resetValidation('folioIne');
    }

    public function depositoSeleccionado(): ?array
    {
        if (! $this->cierreDepositoSeleccionado) {
            return null;
        }

        if ($this->tipoDepositoSeleccionado === 'cierre') {
            $cierre = CierreDevolucionRenta::query()->with('cliente')->where('estatus', 'PendienteCaja')->find($this->cierreDepositoSeleccionado);
            return $cierre ? ['cliente' => $cierre->cliente, 'importe' => (float) $cierre->deposito_a_devolver, 'folio' => 'Cierre #' . $cierre->id] : null;
        }

        if ($this->tipoDepositoSeleccionado === 'movimiento') {
            $movimiento = CajaMovimiento::query()->with('movimentable')->where('tipo', 'Egreso')->where('fuente', 'Devolución depósito renta')->where('aplicado_a_caja', false)->find($this->cierreDepositoSeleccionado);
            $origen = $movimiento?->movimentable;
            $cliente = match (true) {
                $origen instanceof CierreDevolucionRenta => $origen->cliente,
                $origen instanceof DevolucionesRenta => $origen->documentoOrigen?->cliente,
                $origen instanceof NotasVentaRenta => $origen->cliente,
                default => null,
            };
            return $movimiento ? ['cliente' => $cliente, 'importe' => (float) $movimiento->importe, 'folio' => $movimiento->referencia ?: 'Movimiento #' . $movimiento->id] : null;
        }

        return null;
    }

    public function imagenIneSeleccionada(): ?string
    {
        $deposito = $this->depositoSeleccionado();
        $path = data_get($deposito, 'cliente.ine');

        if (blank($path) || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        try {
            return Storage::disk('local')->temporaryUrl($path, now()->addMinutes(20));
        } catch (\Throwable) {
            return null;
        }
    }

    public function procesarDevolucionDeposito(): void
    {
        $this->validate([
            'folioIne' => ['required', 'string', 'max:100'],
        ], [
            'folioIne.required' => 'Capture el Folio de INE para continuar.',
        ]);

        if (! $this->cierreDepositoSeleccionado) {
            return;
        }

        try {
            $service = app(\App\Services\CierreDevolucionRentaService::class);
            $procesado = $this->tipoDepositoSeleccionado === 'cierre'
                ? $service->procesarDepositoPendiente($this->cierreDepositoSeleccionado, (int) Auth::id(), $this->folioIne)
                : $service->procesarMovimientoDepositoPendiente($this->cierreDepositoSeleccionado, (int) Auth::id(), $this->folioIne);

            if (! $procesado) {
                Notification::make()
                    ->title('Caja no disponible')
                    ->body('Abra una caja de la sucursal para registrar el egreso. La devolución permanece pendiente.')
                    ->warning()
                    ->send();
                return;
            }

            $movimientoId = $this->tipoDepositoSeleccionado === 'cierre'
                ? CierreDevolucionRenta::query()->whereKey($this->cierreDepositoSeleccionado)->value('caja_movimiento_id')
                : $this->cierreDepositoSeleccionado;
            if ($movimientoId) {
                $this->dispatch('abrir-ticket-pago', url: route('caja-movimientos.devolucion-deposito.ticket', $movimientoId));
            }

            $this->cancelarConfirmacionDeposito();
            unset($this->notasPendientes, $this->depositosPendientes);
            Notification::make()
                ->title('Devolución aplicada a caja')
                ->body('El egreso quedó registrado y se incluye en los movimientos del día y el corte de caja.')
                ->success()
                ->send();
        } catch (\DomainException $exception) {
            if (str_contains(mb_strtolower($exception->getMessage()), 'folio de ine no coincide')) {
                $this->addError('folioIne', $exception->getMessage());
            } else {
                Notification::make()
                    ->title('No se pudo aplicar la devolución')
                    ->body($exception->getMessage())
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $exception) {
            report($exception);
            Notification::make()
                ->title('No se pudo aplicar la devolución')
                ->body('Revise los datos del cierre y vuelva a intentarlo.')
                ->danger()
                ->send();
        }
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
                'folio' => trim(($nota->serie ?? '').'-'.($nota->folio ?? '')),
                'condicion_pago' => ($nota->condicion_pago ?? 'contado') === 'credito' ? 'Crédito' : 'Contado',
                'credito_confirmado' => $nota->cobro_credito_confirmado_en !== null,
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
                'folio' => trim(($nota->serie ?? '').'-'.($nota->folio ?? '')),
                'condicion_pago' => ($nota->condicion_pago ?? 'contado') === 'credito' ? 'Crédito' : 'Contado',
                'credito_confirmado' => $nota->cobro_credito_confirmado_en !== null,
                'fecha' => optional($nota->fecha_emision)->format('d/m/Y'),
                'cliente' => $nota->cliente?->nombre ?? 'N/A',
                'total' => (float) $nota->total,
                'saldo' => (float) $nota->saldo_pendiente,
            ]);

        return $rentas
            ->concat($ventas)
            ->sortByDesc(fn (array $nota): string => $nota['fecha'] ?? '')
            ->values();
    }

    #[Computed]
    public function depositosPendientes(): Collection
    {
        $cierres = CierreDevolucionRenta::query()
            ->with(['cliente', 'direccionEntrega'])
            ->where('estatus', 'PendienteCaja')
            ->where('deposito_a_devolver', '>', 0)
            ->latest('created_at')
            ->get()
            ->map(fn (CierreDevolucionRenta $cierre): array => [
                'tipo' => 'cierre',
                'id' => $cierre->id,
                'fecha' => optional($cierre->created_at)->format('d/m/Y'),
                'cliente' => $cierre->cliente?->nombre ?? 'N/A',
                'direccion' => $cierre->direccionEntrega?->direccion_completa ?? '—',
                'importe' => (float) $cierre->deposito_a_devolver,
            ]);

        $movimientos = CajaMovimiento::query()
            ->with('movimentable')
            ->where('tipo', 'Egreso')
            ->where('fuente', 'Devolución depósito renta')
            ->where('aplicado_a_caja', false)
            ->latest('fecha')
            ->get()
            ->map(function (CajaMovimiento $movimiento): array {
                $origen = $movimiento->movimentable;
                $cliente = match (true) {
                    $origen instanceof CierreDevolucionRenta => $origen->cliente,
                    $origen instanceof DevolucionesRenta => $origen->documentoOrigen?->cliente,
                    $origen instanceof NotasVentaRenta => $origen->cliente,
                    default => null,
                };

                return [
                    'tipo' => 'movimiento',
                    'id' => $movimiento->id,
                    'fecha' => optional($movimiento->fecha)->format('d/m/Y'),
                    'cliente' => $cliente?->nombre ?? 'N/A',
                    'direccion' => '—',
                    'importe' => (float) $movimiento->importe,
                ];
            });

        return $cierres->concat($movimientos)->sortByDesc('fecha')->values();
    }

    public function saldoSeleccionado(): float
    {
        $nota = $this->notasPendientes->first(
            fn (array $nota): bool => $nota['tipo'] === $this->tipoSeleccionado && $nota['id'] === $this->notaSeleccionada,
        );

        return (float) ($nota['saldo'] ?? 0);
    }

    public function notasDeLaPestana(): Collection
    {
        $hoy = now()->toDateString();

        return $this->notasPendientes->filter(function (array $nota) use ($hoy): bool {
            $fecha = Carbon::createFromFormat('d/m/Y', $nota['fecha'])->toDateString();
            $creditoConfirmado = (bool) ($nota['credito_confirmado'] ?? false);

            return $this->pestana === 'hoy'
                ? ! $creditoConfirmado && $fecha === $hoy
                : $creditoConfirmado || $fecha < $hoy;
        })->values();
    }

    public function condicionPagoSeleccionada(): string
    {
        $nota = $this->notasPendientes->first(
            fn (array $nota): bool => $nota['tipo'] === $this->tipoSeleccionado && $nota['id'] === $this->notaSeleccionada,
        );

        return (string) ($nota['condicion_pago'] ?? 'Contado');
    }

    public function esCreditoSeleccionado(): bool
    {
        return $this->condicionPagoSeleccionada() === 'Crédito';
    }

    public function requiereConfirmacionCredito(): bool
    {
        $nota = $this->notasPendientes->first(
            fn (array $nota): bool => $nota['tipo'] === $this->tipoSeleccionado && $nota['id'] === $this->notaSeleccionada,
        );

        return $this->esCreditoSeleccionado()
            && ! ($nota['credito_confirmado'] ?? false)
            && ($nota['fecha'] ?? null) === now()->format('d/m/Y');
    }

    public function pagarAction(): Action
    {
        return Action::make('pagar')
            ->label('Registrar pago')
            ->modalHeading(fn (): string => $this->requiereConfirmacionCredito() ? 'Confirmar pago de crédito' : 'Registrar pago')
            ->modalSubmitActionLabel('Guardar e imprimir ticket')
            ->modalWidth('5xl')
            ->form(fn (): array => $this->requiereConfirmacionCredito()
                ? [
                    Placeholder::make('confirmacion_credito')
                        ->label('Confirmación')
                        ->content('Se confirma el Ticket a Crédito.')
                        ->columnSpanFull(),
                ]
                : $this->formularioPagoContado())
            ->action(function (array $data): void {
                try {
                    if ($this->requiereConfirmacionCredito()) {
                        app(CobroNotaService::class)->confirmarCredito(
                            $this->tipoSeleccionado,
                            (int) $this->notaSeleccionada,
                        );

                        $ticketUrl = $this->tipoSeleccionado === 'notas_venta_renta'
                            ? route('notas-venta-renta.pdf.ticket', $this->notaSeleccionada)
                            : route('notas-venta-venta.pdf.ticket', $this->notaSeleccionada);

                        $this->dispatch('abrir-ticket-pago', url: $ticketUrl);
                        unset($this->notasPendientes);
                        $this->dispatch('$refresh');
                        Notification::make()
                            ->title('Crédito confirmado')
                            ->body('La nota permanece en Cuentas por Cobrar.')
                            ->success()
                            ->send();

                        return;
                    }

                    $pago = app(CobroNotaService::class)->cobrar(
                        $this->tipoSeleccionado,
                        (int) $this->notaSeleccionada,
                        $data,
                        (int) Auth::id(),
                    );

                    $ticketUrl = $this->tipoSeleccionado === 'notas_venta_renta'
                        ? route('notas-venta-renta.pdf.ticket', $this->notaSeleccionada)
                        : route('notas-venta-venta.pdf.ticket', $this->notaSeleccionada);

                    $this->dispatch('abrir-ticket-pago', url: $ticketUrl);
                    unset($this->notasPendientes);
                    $this->dispatch('$refresh');
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

    private function formularioPagoContado(): array
    {
        return [
            Section::make('Datos del pago')->schema([
                DatePicker::make('fecha_pago')
                    ->label('Fecha de pago')
                    ->default(now())
                    ->required(),
                Repeater::make('pagos')
                    ->label('Formas de pago')
                    ->schema([
                        Select::make('forma_pago')
                            ->label('Forma')
                            ->options([
                                '01' => 'Efectivo',
                                '02' => 'Cheque nominativo',
                                '03' => 'Transferencia electrónica',
                                '04' => 'Tarjeta de crédito',
                                '28' => 'Tarjeta de débito',
                            ])
                            ->default('01')
                            ->live()
                            ->required(),
                        TextInput::make('importe')
                            ->label('Importe aplicado')
                            ->prefix('$')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('importe_recibido')
                            ->label('Efectivo recibido')
                            ->prefix('$')
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => $get('forma_pago') === '01')
                            ->required(fn (Get $get): bool => $get('forma_pago') === '01'),
                    ])
                    ->default(fn (): array => [[
                        'forma_pago' => '01',
                        'importe' => $this->saldoSeleccionado(),
                        'importe_recibido' => $this->saldoSeleccionado(),
                    ]])
                    ->addActionLabel('Agregar otra forma de pago')
                    ->columns(3)
                    ->columnSpanFull(),
                TextInput::make('referencia')
                    ->label('Referencia')
                    ->maxLength(255),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ];
    }
}
