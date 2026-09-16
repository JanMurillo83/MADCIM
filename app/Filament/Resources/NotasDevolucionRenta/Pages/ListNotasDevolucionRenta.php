<?php

namespace App\Filament\Resources\NotasDevolucionRenta\Pages;

use App\Filament\Resources\NotasDevolucionRenta\NotasDevolucionRentaResource;
use Filament\Resources\Pages\ListRecords;

class ListNotasDevolucionRenta extends ListRecords
{
    protected static string $resource = NotasDevolucionRentaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
