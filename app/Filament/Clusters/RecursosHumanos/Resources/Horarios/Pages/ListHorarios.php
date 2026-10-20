<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Horarios\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\Horarios\HorarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHorarios extends ListRecords
{
    protected static string $resource = HorarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
