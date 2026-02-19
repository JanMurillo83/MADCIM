<?php

namespace App\Filament\Resources\Cotizaciones\Pages;

use App\Filament\Resources\Cotizaciones\CotizacionesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCotizaciones extends CreateRecord
{
    protected static string $resource = CotizacionesResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
