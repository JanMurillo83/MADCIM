<?php

namespace App\Filament\Resources\NotasVentaRenta\Pages;

use App\Filament\Resources\NotasVentaRenta\NotasVentaRentaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotasVentaRenta extends EditRecord
{
    protected static string $resource = NotasVentaRentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // Los registros de renta se crean desde las Notas de Envío
}
