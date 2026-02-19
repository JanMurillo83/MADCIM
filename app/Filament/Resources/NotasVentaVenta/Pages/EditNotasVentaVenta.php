<?php

namespace App\Filament\Resources\NotasVentaVenta\Pages;

use App\Filament\Resources\NotasVentaVenta\NotasVentaVentaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotasVentaVenta extends EditRecord
{
    protected static string $resource = NotasVentaVentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
