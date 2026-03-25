<?php

namespace App\Filament\Resources\NotasVentaRenta\Pages;

use App\Filament\Resources\NotasVentaRenta\NotasVentaRentaResource;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateNotasVentaRenta extends CreateRecord
{
    protected static string $resource = NotasVentaRentaResource::class;

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
