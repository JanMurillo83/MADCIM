<?php

namespace App\Filament\Resources\NotasRentadas\Pages;

use App\Filament\Resources\NotasRentadas\NotasRentadasResource;
use Filament\Resources\Pages\ListRecords;

class ListNotasRentadas extends ListRecords
{
    protected static string $resource = NotasRentadasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
