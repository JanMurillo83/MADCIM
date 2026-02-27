<?php

namespace App\Filament\Resources\RecepcionesCompra\Pages;

use App\Filament\Resources\RecepcionesCompra\RecepcionesCompraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecepcionesCompra extends CreateRecord
{
    protected static string $resource = RecepcionesCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
