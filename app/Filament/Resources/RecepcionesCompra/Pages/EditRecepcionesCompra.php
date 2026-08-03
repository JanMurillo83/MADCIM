<?php

namespace App\Filament\Resources\RecepcionesCompra\Pages;

use App\Filament\Resources\RecepcionesCompra\RecepcionesCompraResource;
use App\Services\RecepcionCompraInventoryService;
use Filament\Resources\Pages\EditRecord;

class EditRecepcionesCompra extends EditRecord
{
    protected static string $resource = RecepcionesCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        if ($this->record->orden_compra_id === null) {
            return;
        }

        app(RecepcionCompraInventoryService::class)->cerrar($this->record);
    }
}
