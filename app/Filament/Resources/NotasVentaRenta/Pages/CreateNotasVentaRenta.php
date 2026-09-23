<?php

namespace App\Filament\Resources\NotasVentaRenta\Pages;

use App\Enums\TipoNotaRenta;
use App\Filament\Resources\NotasVentaRenta\NotasVentaRentaResource;
use App\Models\Clientes;
use App\Models\Productos;
use App\Services\RentaMaderaM2Service;
use App\Support\Impuestos;
use Carbon\Carbon;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class CreateNotasVentaRenta extends CreateRecord
{
    protected static string $resource = NotasVentaRentaResource::class;

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
                ->modalHeading('Confirmar nota de renta')
                ->modalDescription(fn () => $this->buildRentaPeriodoDescription())
                ->modalSubmitActionLabel('Guardar')
                ->modalCancelActionLabel('Revisar')
                ->action(fn () => $this->guardarCaptura()),
        ];
    }

    public function guardarCaptura(array $data = []): void
    {
        try {
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
            . '<li><strong>Total de la nota:</strong> $' . $total . '</li>'
            . '<li><strong>Pago:</strong> se realizará posteriormente en Caja.</li>'
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
