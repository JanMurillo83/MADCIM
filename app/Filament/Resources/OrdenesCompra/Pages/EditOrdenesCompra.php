<?php

namespace App\Filament\Resources\OrdenesCompra\Pages;

use App\Filament\Resources\OrdenesCompra\OrdenesCompraResource;
use Filament\Resources\Pages\EditRecord;

class EditOrdenesCompra extends EditRecord
{
    protected static string $resource = OrdenesCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
