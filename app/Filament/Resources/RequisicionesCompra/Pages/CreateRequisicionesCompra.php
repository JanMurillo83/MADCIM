<?php

namespace App\Filament\Resources\RequisicionesCompra\Pages;

use App\Filament\Resources\RequisicionesCompra\RequisicionesCompraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequisicionesCompra extends CreateRecord
{
    protected static string $resource = RequisicionesCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
