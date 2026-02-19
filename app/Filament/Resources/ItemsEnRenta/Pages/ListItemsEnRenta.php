<?php

namespace App\Filament\Resources\ItemsEnRenta\Pages;

use App\Filament\Resources\ItemsEnRenta\ItemsEnRentaResource;
use Filament\Resources\Pages\ListRecords;

class ListItemsEnRenta extends ListRecords
{
    protected static string $resource = ItemsEnRentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
