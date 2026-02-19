<?php

namespace App\Filament\Resources\DevolucionesRenta\Pages;

use App\Filament\Resources\DevolucionesRenta\DevolucionesRentaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDevolucionesRenta extends EditRecord
{
    protected static string $resource = DevolucionesRentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
