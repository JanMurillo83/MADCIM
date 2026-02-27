<?php

namespace App\Filament\Resources\OrdenesCompra\Pages;

use App\Filament\Resources\OrdenesCompra\OrdenesCompraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrdenesCompra extends CreateRecord
{
    protected static string $resource = OrdenesCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
