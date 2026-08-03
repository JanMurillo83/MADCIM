<?php

namespace App\Filament\Resources\RecepcionesCompra\Pages;

use App\Filament\Resources\RecepcionesCompra\RecepcionesCompraResource;
use App\Services\RecepcionCompraInventoryService;
use Filament\Resources\Pages\CreateRecord;

class CreateRecepcionesCompra extends CreateRecord
{
    protected static string $resource = RecepcionesCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        if ($this->record->orden_compra_id !== null) {
            return;
        }

        app(RecepcionCompraInventoryService::class)->cerrar($this->record);
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
