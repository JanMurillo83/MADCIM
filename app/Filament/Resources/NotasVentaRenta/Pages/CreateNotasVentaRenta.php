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
        // Calcular fecha de vencimiento basada en días de renta
        $fechaEmision = Carbon::parse($data['fecha_emision'] ?? now());
        $diasRenta = !empty($data['dias_renta']) ? (int) $data['dias_renta'] : 30;
        $data['fecha_vencimiento'] = $fechaEmision->addDays($diasRenta)->toDateString();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
