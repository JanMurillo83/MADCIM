<?php

namespace App\Filament\Resources\RecepcionesCompra\Pages;

use App\Filament\Resources\RecepcionesCompra\RecepcionesCompraResource;
use Filament\Resources\Pages\EditRecord;

class EditRecepcionesCompra extends EditRecord
{
    protected static string $resource = RecepcionesCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
