<?php

namespace App\Filament\Resources\NotasVentaRenta\Pages;

use App\Enums\TipoNotaRenta;
use App\Filament\Resources\NotasVentaRenta\NotasVentaRentaResource;
use App\Models\Clientes;
use App\Models\Productos;
use App\Services\DesgloseM2Service;
use App\Services\RentaMaderaM2Service;
use Carbon\Carbon;
use DomainException;
use Filament\Actions\Action;
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
                ->modalHeading('Confirmar periodo de renta')
                ->modalDescription(fn () => $this->buildRentaPeriodoDescription())
                ->modalSubmitActionLabel('Guardar')
                ->modalCancelActionLabel('Revisar')
                ->action(fn () => $this->guardarCaptura()),
        ];
    }

    public function guardarCaptura(): void
    {
        $this->create();
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
            $cantidadDesglose = collect($data['desglose_m2'] ?? [])
                ->sum(fn ($fila) => (float) ($fila['cantidad'] ?? 0));

            return new HtmlString(
                '<p class="mb-2">Por favor revise los datos de la renta M2 antes de guardar:</p>'
                . '<ul class="list-disc pl-5 space-y-1">'
                . "<li><strong>Tipo de Nota de Renta:</strong> {$tipoNotaRenta->label()}</li>"
                . "<li><strong>Metros cuadrados:</strong> {$metros} M2</li>"
                . "<li><strong>Días de renta:</strong> {$diasRenta}</li>"
                . "<li><strong>Fecha de vencimiento:</strong> {$fechaVencimiento}</li>"
                . "<li><strong>Total de piezas en desglose:</strong> {$cantidadDesglose}</li>"
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
            $cliente?->validarCreacionNota($condicionPago);
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
                $erroresExistencia = DesgloseM2Service::validarExistencias($data['desglose_m2'] ?? []);
                if ($erroresExistencia !== []) {
                    throw ValidationException::withMessages([
                        'desglose_m2' => implode(' ', $erroresExistencia),
                    ]);
                }

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
        $tipoNotaRenta = TipoNotaRenta::tryFrom($record->tipo_nota_renta ?? '');

        if ($tipoNotaRenta?->esMaderaM2() !== true) {
            return;
        }

        $desglose = $this->data['desglose_m2'] ?? [];
        foreach ($desglose as $fila) {
            $record->desgloseM2()->create([
                'producto_id' => $fila['producto_id'] ?? null,
                'clave' => $fila['clave'] ?? null,
                'descripcion' => $fila['descripcion'] ?? null,
                'cantidad' => $fila['cantidad'] ?? 0,
                'm2_cubre' => $fila['m2_cubre'] ?? 0,
                'm2_total' => $fila['m2_total'] ?? 0,
                'tipo_madera' => $tipoNotaRenta->tipoMaderaParaDesglose(),
                'observaciones' => $fila['observaciones'] ?? null,
            ]);
        }
    }

    // Los registros de renta se crean desde las Notas de Envío

    protected function getRedirectUrl(): string
    {
        return route('notas-venta-renta.preview', ['id' => $this->record->id]);
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
