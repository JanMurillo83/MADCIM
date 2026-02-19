<?php

namespace App\Filament\Resources\DevolucionesVenta\Pages;

use App\Filament\Resources\DevolucionesVenta\DevolucionesVentaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDevolucionesVenta extends EditRecord
{
    protected static string $resource = DevolucionesVentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
