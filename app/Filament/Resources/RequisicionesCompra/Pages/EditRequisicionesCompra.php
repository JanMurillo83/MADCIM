<?php

namespace App\Filament\Resources\RequisicionesCompra\Pages;

use App\Filament\Resources\RequisicionesCompra\RequisicionesCompraResource;
use Filament\Resources\Pages\EditRecord;

class EditRequisicionesCompra extends EditRecord
{
    protected static string $resource = RequisicionesCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
