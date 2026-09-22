<?php

namespace App\Filament\Resources\NotasVentaRenta\Pages;

use App\Enums\TipoNotaRenta;
use App\Filament\Resources\NotasVentaRenta\NotasVentaRentaResource;
use App\Models\Caja;
use App\Models\Clientes;
use App\Models\Pagos;
use App\Models\Productos;
use App\Services\RentaMaderaM2Service;
use App\Support\Impuestos;
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
        $fechaEmision = $data['fecha_emision'] ?? Carbon::now()->toDateString();
        $total = number_format((float) ($data['total'] ?? 0), 2);
        $tipos = collect($data['partidas'] ?? [])
            ->map(fn ($partida) => TipoNotaRenta::tryFrom($partida['tipo_nota_renta'] ?? 'equipo'))
            ->filter()
            ->unique()
            ->map(fn (TipoNotaRenta $tipo) => $tipo->label())
            ->implode(', ');
        $fechaVencimiento = collect($data['partidas'] ?? [])
            ->map(function (array $partida) use ($fechaEmision): Carbon {
                $tipo = TipoNotaRenta::tryFrom($partida['tipo_nota_renta'] ?? 'equipo') ?? TipoNotaRenta::Equipo;
                $dias = $tipo->esMadera()
                    ? min(30, max(1, (int) ($partida['dias_renta'] ?? 1)))
                    : $this->calcularDiasRentaEquipo($partida);
                return Carbon::parse($fechaEmision)->addDays($dias);
            })
            ->sort()
            ->last()?->toDateString() ?? Carbon::parse($fechaEmision)->toDateString();

        return new HtmlString(
            '<p class="mb-2">Por favor revise las partidas de renta antes de guardar:</p>'
            . '<ul class="list-disc pl-5 space-y-1">'
            . "<li><strong>Tipos de renta:</strong> {$tipos}</li>"
            . "<li><strong>Fecha de vencimiento:</strong> {$fechaVencimiento}</li>"
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
        $partidasCapturadas = $this->form->getRawState()['partidas'] ?? [];
        if (is_array($partidasCapturadas) && $partidasCapturadas !== []) {
            $data['partidas'] = $partidasCapturadas;
        }

        $cliente = Clientes::find($data['cliente_id'] ?? null);
        $condicionPago = $data['condicion_pago'] ?? 'contado';

        try {
            $cliente?->validarCreacionNota($condicionPago, requiereFolioIne: true);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'cliente_id' => $exception->getMessage(),
            ]);
        }

        $fechaEmision = Carbon::parse($data['fecha_emision'] ?? now());
        $data['fecha_vencimiento_pago'] = $condicionPago === 'credito'
            ? $fechaEmision->copy()->addDays(max(1, (int) ($cliente?->dias_credito ?? 0)))->toDateString()
            : null;

        $partidas = [];
        $subtotal = 0.0;
        $impuestos = 0.0;
        $deposito = 0.0;
        $vencimientoMaximo = $fechaEmision->copy();

        foreach ($data['partidas'] ?? [] as $partida) {
            $tipo = TipoNotaRenta::tryFrom($partida['tipo_nota_renta'] ?? $data['tipo_nota_renta'] ?? 'equipo')
                ?? TipoNotaRenta::Equipo;
            $cantidad = max(0.0, (float) ($partida['cantidad'] ?? 0));
            $tipoRenta = $partida['tipo_renta'] ?? 'dia';
            $duracion = max(1, (int) ($partida['duracion_renta'] ?? 1));
            $dias = $tipo->esMadera()
                ? min(30, max(1, (int) ($partida['dias_renta'] ?? 1)))
                : match ($tipoRenta) {
                    'semana' => $duracion * 7,
                    'mes' => $duracion * 30,
                    default => $duracion,
                };

            $productoId = $partida['item'] ?? null;
            if ($tipo->esMaderaM2()) {
                $productoId = RentaMaderaM2Service::productoRentaM2Id($tipo);
            }
            $producto = Productos::find($productoId);
            if (!$producto) {
                continue;
            }

            if ($tipo->esMaderaM2()) {
                $metros = max(0.0, (float) ($partida['metros_m2'] ?? 0));
                $calculo = RentaMaderaM2Service::calcular($tipo, $metros);
                $partida['item'] = $producto->id;
                $partida['descripcion'] = $producto->descripcion . ' - ' . $metros . ' M2';
                $partida['cantidad'] = 1;
                $partida['valor_unitario'] = $calculo['total_renta'];
                $partida['subtotal'] = $calculo['subtotal_renta'];
                $partida['impuestos'] = $calculo['iva_renta'];
                $partida['total'] = $calculo['total_renta'];
                $partida['deposito'] = $calculo['deposito'];
            } else {
                $precioBase = $tipo->esMadera()
                    ? (float) $producto->precio_renta_dia
                    : match ($tipoRenta) {
                        'semana' => (float) $producto->precio_renta_semana,
                        'mes' => (float) $producto->precio_renta_mes,
                        default => (float) $producto->precio_renta_dia,
                    };
                $valorUnitario = $tipo->esMadera() ? $precioBase : round($precioBase * $duracion, 2);
                $totalConIva = round($cantidad * $valorUnitario, 2);
                $desglose = Impuestos::desglosarIvaIncluido($totalConIva);
                $partida['valor_unitario'] = $valorUnitario;
                $partida['subtotal'] = $desglose['subtotal'];
                $partida['impuestos'] = $desglose['iva'];
                $partida['total'] = $totalConIva;
                $partida['deposito'] = $tipo->esMadera() ? round($totalConIva * 0.50, 2) : 0;
            }

            $partida['tipo_nota_renta'] = $tipo->value;
            $partida['tipo_renta'] = $tipo->esMadera() ? 'dia' : $tipoRenta;
            $partida['duracion_renta'] = $duracion;
            $partida['dias_renta'] = $dias;
            $partida['fecha_vencimiento'] = $fechaEmision->copy()->addDays($dias)->toDateString();
            $partida['metros_m2'] = $partida['metros_m2'] ?? null;
            $partida['deposito'] = (float) ($partida['deposito'] ?? 0);
            $partidas[] = $partida;
            $subtotal += (float) $partida['subtotal'];
            $impuestos += (float) $partida['impuestos'];
            $deposito += (float) $partida['deposito'];
            $vencimientoPartida = $fechaEmision->copy()->addDays($dias);
            if ($vencimientoPartida->greaterThan($vencimientoMaximo)) {
                $vencimientoMaximo = $vencimientoPartida;
            }
        }

        $data['partidas'] = $partidas;
        $data['subtotal'] = round($subtotal, 2);
        $data['impuestos_total'] = round($impuestos, 2);
        $data['deposito'] = round($deposito, 2);
        $data['total'] = round($subtotal + $impuestos + $deposito, 2);
        $data['saldo_pendiente'] = $data['total'];
        $data['fecha_vencimiento'] = $vencimientoMaximo->toDateString();
        $data['dias_renta'] = max(1, $fechaEmision->diffInDays($vencimientoMaximo, true));

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

        $record->load('partidas');
        $subtotal = (float) $record->partidas->sum('subtotal');
        $impuestos = (float) $record->partidas->sum('impuestos');
        $deposito = (float) $record->partidas->sum('deposito');
        $total = round($subtotal + $impuestos + $deposito, 2);

        $record->forceFill([
            'subtotal' => round($subtotal, 2),
            'impuestos_total' => round($impuestos, 2),
            'deposito' => round($deposito, 2),
            'total' => $total,
            'saldo_pendiente' => $total,
        ])->saveQuietly();

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
