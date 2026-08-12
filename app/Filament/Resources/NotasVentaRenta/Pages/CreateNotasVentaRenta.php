<?php

namespace App\Filament\Resources\NotasVentaRenta\Pages;

use App\Filament\Resources\NotasVentaRenta\NotasVentaRentaResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\HtmlString;

class CreateNotasVentaRenta extends CreateRecord
{
    protected static string $resource = NotasVentaRentaResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->requiresConfirmation()
            ->modalHeading('Confirmar periodo de renta')
            ->modalDescription(fn () => $this->buildRentaPeriodoDescription())
            ->modalSubmitActionLabel('Guardar')
            ->modalCancelActionLabel('Revisar');
    }

    private function buildRentaPeriodoDescription(): HtmlString
    {
        $data = $this->data ?? [];
        $tipoRenta = $data['tipo_renta'] ?? 'dia';
        $duracion = (int) ($data['duracion_renta'] ?? 1);
        $fechaEmision = $data['fecha_emision'] ?? Carbon::now()->toDateString();

        $diasRenta = match ($tipoRenta) {
            'semana' => $duracion * 7,
            'mes' => $duracion * 30,
            default => $duracion,
        };

        $fechaVencimiento = Carbon::parse($fechaEmision)->addDays($diasRenta)->toDateString();

        [$tipoLabel, $unidad] = match ($tipoRenta) {
            'semana' => ['Por Semana', 'semana(s)'],
            'mes' => ['Por Mes', 'mes(es)'],
            default => ['Por Día', 'día(s)'],
        };

        $cantidadTotal = collect($data['partidas'] ?? [])
            ->sum(fn ($partida) => (float) ($partida['cantidad'] ?? 0));

        $total = number_format((float) ($data['total'] ?? 0), 2);

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calcular duración equivalente en días para vencimiento/registros.
        $fechaEmision = Carbon::parse($data['fecha_emision'] ?? now());
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
