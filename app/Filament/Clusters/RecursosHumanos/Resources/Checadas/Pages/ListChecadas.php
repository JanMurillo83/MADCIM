<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Checadas\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\Checadas\ChecadaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChecadas extends ListRecords
{
    protected static string $resource = ChecadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
