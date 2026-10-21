<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Contratos\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\Contratos\ContratoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContratos extends ListRecords
{
    protected static string $resource = ContratoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
