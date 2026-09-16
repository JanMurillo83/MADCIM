<?php

namespace App\Filament\Resources\NotasVentaVenta\Pages;

use App\Filament\Resources\NotasVentaVenta\NotasVentaVentaResource;
use App\Models\Caja;
use App\Models\Clientes;
use App\Models\Pagos;
use App\Models\Productos;
use Carbon\Carbon;
use DomainException;
use App\Services\InventarioMovimientoService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateNotasVentaVenta extends CreateRecord
{
    protected static string $resource = NotasVentaVentaResource::class;

    public ?array $pagoCapturado = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancelar_captura')
                ->label('Cancelar')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancelar captura')
                ->modalDescription('¿Deseas cancelar la captura y regresar al listado? Se perderán los datos no guardados.')
                ->modalSubmitActionLabel('Sí, cancelar')
                ->action(fn () => $this->cancelarCaptura()),
            Action::make('guardar')
                ->label('Guardar')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Confirmar nota de venta')
                ->modalSubmitActionLabel('Guardar')
                ->modalCancelActionLabel('Revisar')
                ->form(fn (): array => $this->formularioPagosContado())
                ->action(fn (array $data) => $this->guardarConPago($data)),
        ];
    }

    public function guardarConPago(array $data): void
    {
        if (($this->data['condicion_pago'] ?? 'contado') === 'contado') {
            $pagos = $data['pagos'] ?? [];
            $totalNota = round((float) ($this->data['total'] ?? 0), 2);
            $totalAplicado = round(array_sum(array_map(
                static fn (array $pago): float => (float) ($pago['importe'] ?? 0),
                $pagos
            )), 2);

            if (abs($totalAplicado - $totalNota) > 0.009) {
                throw ValidationException::withMessages([
                    'pagos' => 'La suma de los importes aplicados debe cubrir exactamente el total de la nota.',
                ]);
            }

            foreach ($pagos as $indice => $pago) {
                $importe = (float) ($pago['importe'] ?? 0);
                $recibido = (float) ($pago['importe_recibido'] ?? $importe);

                if ($importe <= 0) {
                    throw ValidationException::withMessages([
                        "pagos.{$indice}.importe" => 'El importe aplicado debe ser mayor a cero.',
                    ]);
                }

                if (($pago['metodo_pago'] ?? null) === '01' && $recibido < $importe) {
                    throw ValidationException::withMessages([
                        "pagos.{$indice}.importe_recibido" => 'El importe recibido no puede ser menor al importe aplicado.',
                    ]);
                }
            }

            $this->pagoCapturado = $pagos;
        } else {
            $this->pagoCapturado = null;
        }

        $this->create();
    }

    public function formularioPagosContado(): array
    {
        return [
            Placeholder::make('resumen_pagos')
                ->label('Resumen del pago')
                ->content(function (Get $get): string {
                    $total = round((float) ($this->data['total'] ?? 0), 2);
                    $pagos = $get('pagos') ?? [];
                    $aplicado = round(is_array($pagos) ? array_sum(array_map(
                        static fn (array $pago): float => (float) ($pago['importe'] ?? 0),
                        $pagos
                    )) : 0, 2);
                    $faltante = round(max($total - $aplicado, 0), 2);

                    return sprintf(
                        'Total de la nota: $%s | Aplicado: $%s | Falta por cubrir: $%s',
                        number_format($total, 2),
                        number_format($aplicado, 2),
                        number_format($faltante, 2)
                    );
                })
                ->live(),
            Repeater::make('pagos')
                ->label('Formas de pago')
                ->visible(fn (): bool => ($this->data['condicion_pago'] ?? 'contado') === 'contado')
                ->schema([
                    Select::make('metodo_pago')
                        ->label('Forma de pago')
                        ->options([
                            '01' => 'Efectivo',
                            '02' => 'Cheque',
                            '03' => 'Transferencia',
                            '04' => 'Tarjeta de crédito',
                            '28' => 'Tarjeta de débito',
                        ])
                        ->default('01')
                        ->live()
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                            if ($state === '01' && blank($get('importe_recibido'))) {
                                $set('importe_recibido', $get('importe') ?? 0);
                            }
                        })
                        ->required(),
                    TextInput::make('importe')
                        ->label('Importe aplicado a la nota')
                        ->numeric()
                        ->prefix('$')
                        ->default(fn (Get $get): float => $this->importeRestante($get))
                        ->helperText('Monto de esta forma de pago que se abona al total.')
                        ->live(onBlur: true)
                        ->required()
                        ->minValue(0.01),
                    TextInput::make('importe_recibido')
                        ->label('Efectivo recibido')
                        ->numeric()
                        ->prefix('$')
                        ->default(fn (Get $get): float => (float) ($get('importe') ?? 0))
                        ->helperText('Cantidad física recibida. El excedente se registra como cambio.')
                        ->required(fn (Get $get): bool => $get('metodo_pago') === '01')
                        ->minValue(0)
                        ->visible(fn (Get $get): bool => $get('metodo_pago') === '01'),
                ])
                ->defaultItems(1)
                ->addActionLabel('Agregar forma de pago')
                ->reorderable(false)
                ->required(),
        ];
    }

    private function importeRestante(Get $get): float
    {
        $pagos = $get('../../pagos') ?? [];
        $total = (float) ($this->data['total'] ?? 0);
        $aplicado = is_array($pagos) ? array_sum(array_map(
            static fn (array $pago): float => (float) ($pago['importe'] ?? 0),
            $pagos
        )) : 0;

        return round(max($total - $aplicado, 0), 2);
    }

    public function cancelarCaptura(): void
    {
        $this->redirect($this->getResource()::getUrl('index'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $cliente = Clientes::find($data['cliente_id'] ?? null);
        $condicionPago = $data['condicion_pago'] ?? 'contado';

        try {
            $cliente?->validarCreacionNota($condicionPago);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'cliente_id' => $exception->getMessage(),
            ]);
        }

        $fechaEmision = Carbon::parse($data['fecha_emision'] ?? now());
        $data['fecha_vencimiento_pago'] = $condicionPago === 'credito'
            ? $fechaEmision->copy()->addDays(max(1, (int) ($cliente?->dias_credito ?? 0)))->toDateString()
            : null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $referencia = $record->serie . $record->folio;

        foreach ($record->partidas as $partida) {
            $producto = Productos::find($partida->item);
            if (! $producto) {
                continue;
            }

            InventarioMovimientoService::salida(
                productoId: $producto->id,
                cantidad: (float) $partida->cantidad,
                motivo: "Venta generada en nota {$referencia}",
                documentoReferencia: $referencia
            );
        }

        if ($record->condicion_pago === 'contado' && $this->pagoCapturado) {
            $userId = Auth::id();

            foreach ($this->pagoCapturado as $pagoCapturado) {
                $formaPago = $pagoCapturado['metodo_pago'];
                $importe = (float) $pagoCapturado['importe'];
                $esEfectivo = $formaPago === '01';
                $importeRecibido = $esEfectivo
                    ? (float) ($pagoCapturado['importe_recibido'] ?? $importe)
                    : $importe;

                Pagos::create([
                    'documento_tipo' => 'notas_venta_venta',
                    'documento_id' => $record->id,
                    'cliente_id' => $record->cliente_id,
                    'fecha_pago' => now(),
                    'forma_pago' => $formaPago,
                    'importe' => $importe,
                    'importe_recibido' => $importeRecibido,
                    'cambio' => $esEfectivo ? round($importeRecibido - $importe, 2) : 0,
                    'referencia' => 'Pago de contado al crear nota de venta',
                    'user_id' => $userId,
                    'caja_id' => $esEfectivo
                        ? Caja::where('estatus', 'Abierta')->where('usuario_apertura_id', $userId)->value('id')
                        : null,
                ]);
            }
        }

        $ticketUrl = route('notas-venta-venta.pdf.ticket', ['id' => $record->id]);
        $this->js("window.open('{$ticketUrl}', '_blank');");
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->hidden();
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->hidden();
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
