<?php

namespace App\Filament\Resources\NotasVentaRenta\Pages;

use App\Enums\TipoNotaRenta;
use App\Filament\Resources\NotasVentaRenta\NotasVentaRentaResource;
use App\Models\Caja;
use App\Models\Clientes;
use App\Models\Pagos;
use App\Models\Productos;
use App\Services\RentaMaderaM2Service;
use Carbon\Carbon;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class CreateNotasVentaRenta extends CreateRecord
{
    protected static string $resource = NotasVentaRentaResource::class;

    public ?array $pagoCapturado = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancelar_captura')
                ->label('Cancelar')
                ->icon('fas-ban')
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
                ->modalHeading('Confirmar periodo y pago')
                ->modalDescription(fn () => $this->buildRentaPeriodoDescription())
                ->modalSubmitActionLabel('Guardar')
                ->modalCancelActionLabel('Revisar')
                ->form(fn (): array => $this->formularioPagosContado())
                ->action(fn (array $data) => $this->guardarCaptura($data)),
        ];
    }

    public function guardarCaptura(array $data = []): void
    {
        try {
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
        } catch (ValidationException $exception) {
            $mensaje = collect($exception->errors())
                ->flatten()
                ->filter()
                ->implode(' ');

            Notification::make()
                ->danger()
                ->title('No se pudo guardar la nota')
                ->body($mensaje ?: 'Revise los datos capturados e intente nuevamente.')
                ->persistent()
                ->send();
        }
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

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->hidden();
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()->hidden();
    }

    public function buildRentaPeriodoDescription(): HtmlString
    {
        $data = $this->data ?? [];
        $tipoNotaRenta = TipoNotaRenta::tryFrom($data['tipo_nota_renta'] ?? '');
        $esMaderaM2 = $tipoNotaRenta?->esMaderaM2() ?? false;
        $esMadera = $tipoNotaRenta?->esMadera() ?? false;

        $fechaEmision = $data['fecha_emision'] ?? Carbon::now()->toDateString();
        $diasRenta = $esMaderaM2
            ? min(30, max(1, (int) ($data['dias_solicitados'] ?? 1)))
            : ($esMadera
                ? max(1, (int) ($data['dias_solicitados'] ?? 1))
                : $this->calcularDiasRentaEquipo($data));
        $fechaVencimiento = Carbon::parse($fechaEmision)->addDays($diasRenta)->toDateString();
        $total = number_format((float) ($data['total'] ?? 0), 2);

        if ($esMaderaM2) {
            $metros = number_format((float) ($data['metros_m2'] ?? 0), 2);
            return new HtmlString(
                '<p class="mb-2">Por favor revise los datos de la renta M2 antes de guardar:</p>'
                . '<ul class="list-disc pl-5 space-y-1">'
                . "<li><strong>Tipo de Nota de Renta:</strong> {$tipoNotaRenta->label()}</li>"
                . "<li><strong>Metros cuadrados:</strong> {$metros} M2</li>"
                . "<li><strong>Días de renta:</strong> {$diasRenta}</li>"
                . "<li><strong>Fecha de vencimiento:</strong> {$fechaVencimiento}</li>"
                . "<li><strong>Total a pagar:</strong> \${$total}</li>"
                . '</ul>'
            );
        }

        if ($esMadera) {
            $diasSolicitados = (int) ($data['dias_solicitados'] ?? 1);
            $cantidadTotal = collect($data['partidas'] ?? [])
                ->sum(fn ($partida) => (float) ($partida['cantidad'] ?? 0));

            return new HtmlString(
                '<p class="mb-2">Por favor revise los datos de la renta de madera antes de guardar:</p>'
                . '<ul class="list-disc pl-5 space-y-1">'
                . "<li><strong>Tipo de Nota de Renta:</strong> {$tipoNotaRenta->label()}</li>"
                . "<li><strong>Días solicitados:</strong> {$diasSolicitados}</li>"
                . "<li><strong>Fecha de vencimiento:</strong> {$fechaVencimiento}</li>"
                . "<li><strong>Cantidad total de artículos:</strong> {$cantidadTotal}</li>"
                . "<li><strong>Total a pagar:</strong> \${$total}</li>"
                . '</ul>'
            );
        }

        $tipoRenta = $data['tipo_renta'] ?? 'dia';
        $duracion = (int) ($data['duracion_renta'] ?? 1);

        [$tipoLabel, $unidad] = match ($tipoRenta) {
            'semana' => ['Por Semana', 'semana(s)'],
            'mes' => ['Por Mes', 'mes(es)'],
            default => ['Por Día', 'día(s)'],
        };

        $cantidadTotal = collect($data['partidas'] ?? [])
            ->sum(fn ($partida) => (float) ($partida['cantidad'] ?? 0));

        return new HtmlString(
            '<p class="mb-2">Por favor revise el periodo de renta antes de guardar:</p>'
            . '<ul class="list-disc pl-5 space-y-1">'
            . "<li><strong>Tipo de renta:</strong> {$tipoLabel}</li>"
            . "<li><strong>Duración:</strong> {$duracion} {$unidad}</li>"
            . "<li><strong>Fecha de vencimiento:</strong> {$fechaVencimiento}</li>"
            . "<li><strong>Cantidad total de artículos:</strong> {$cantidadTotal}</li>"
            . "<li><strong>Total a pagar:</strong> \${$total}</li>"
            . '</ul>'
        );
    }

    private function calcularDiasRentaEquipo(array $data): int
    {
        $duracion = max(1, (int) ($data['duracion_renta'] ?? 1));

        return match ($data['tipo_renta'] ?? 'dia') {
            'semana' => $duracion * 7,
            'mes' => $duracion * 30,
            default => $duracion,
        };
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $cliente = Clientes::find($data['cliente_id'] ?? null);
        $condicionPago = $data['condicion_pago'] ?? 'contado';

        try {
            $cliente?->validarCreacionNota($condicionPago, requiereFolioIne: true);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'cliente_id' => $exception->getMessage(),
            ]);
        }

        $tipoNotaRenta = TipoNotaRenta::tryFrom($data['tipo_nota_renta'] ?? '');
        $fechaEmision = Carbon::parse($data['fecha_emision'] ?? now());
        $data['fecha_vencimiento_pago'] = $condicionPago === 'credito'
            ? $fechaEmision->copy()->addDays(max(1, (int) ($cliente?->dias_credito ?? 0)))->toDateString()
            : null;

        if ($tipoNotaRenta?->esMadera() === true) {
            // Para madera (pieza o M2) el precio es fijo, pero los días determinan el vencimiento.
            $data['duracion_renta'] = 1;
            $data['tipo_renta'] = 'dia';

            $diasRenta = min(30, max(1, (int) ($data['dias_solicitados'] ?? 1)));

            $data['dias_renta'] = $diasRenta;
            $data['fecha_vencimiento'] = $fechaEmision->copy()->addDays($diasRenta)->toDateString();

            if ($tipoNotaRenta->esMaderaM2()) {
                $data = $this->prepareM2Partidas($data, $tipoNotaRenta);
            }

            return $data;
        }

        // Calcular duración equivalente en días para vencimiento/registros.
        $duracionRenta = !empty($data['duracion_renta']) ? (int) $data['duracion_renta'] : 1;
        $tipoRenta = $data['tipo_renta'] ?? 'dia';
        $diasRenta = match ($tipoRenta) {
            'semana' => $duracionRenta * 7,
            'mes' => $duracionRenta * 30,
            default => $duracionRenta,
        };

        $data['duracion_renta'] = $duracionRenta;
        $data['dias_renta'] = $diasRenta;
        $data['fecha_vencimiento'] = $fechaEmision->addDays($diasRenta)->toDateString();

        return $data;
    }

    private function prepareM2Partidas(array $data, TipoNotaRenta $tipoNotaRenta): array
    {
        $metros = (float) ($data['metros_m2'] ?? 0);
        $calculo = RentaMaderaM2Service::calcular($tipoNotaRenta, $metros);
        $productoId = RentaMaderaM2Service::productoRentaM2Id($tipoNotaRenta);
        $producto = Productos::find($productoId);

        if (!$producto) {
            return $data;
        }

        $subtotal = $calculo['subtotal_renta'];
        $iva = $calculo['iva_renta'];
        $totalConIva = $calculo['total_renta'];

        $data['partidas'] = [
            [
                'item' => $producto->id,
                'descripcion' => $producto->descripcion . ' - ' . $metros . ' M2',
                'cantidad' => 1,
                'valor_unitario' => $totalConIva,
                'subtotal' => $subtotal,
                'impuestos' => $iva,
                'total' => $totalConIva,
            ],
        ];

        // Asegurar que los totales coincidan
        $data['subtotal'] = $subtotal;
        $data['impuestos_total'] = $iva;
        $data['deposito'] = $calculo['deposito'];
        $data['total'] = $calculo['total'];
        $data['saldo_pendiente'] = $calculo['total'];

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

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
                    'documento_tipo' => 'notas_venta_renta',
                    'documento_id' => $record->id,
                    'cliente_id' => $record->cliente_id,
                    'fecha_pago' => now(),
                    'forma_pago' => $formaPago,
                    'importe' => $importe,
                    'importe_recibido' => $importeRecibido,
                    'cambio' => $esEfectivo ? round($importeRecibido - $importe, 2) : 0,
                    'referencia' => 'Pago de contado al crear nota de renta',
                    'user_id' => $userId,
                    'caja_id' => $esEfectivo
                        ? Caja::where('estatus', 'Abierta')->where('usuario_apertura_id', $userId)->value('id')
                        : null,
                ]);
            }
        }

        $ticketUrl = route('notas-venta-renta.pdf.ticket', ['id' => $record->id]);
        $this->js("window.open('{$ticketUrl}', '_blank');");
    }

    // Los registros de renta se crean desde las Notas de Envío

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
