<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\IncidenciaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIncidencias extends ListRecords
{
    protected static string $resource = IncidenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
