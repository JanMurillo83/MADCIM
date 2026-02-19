<?php

namespace App\Filament\Resources\ClienteDireccionEntregas\Pages;

use App\Filament\Resources\ClienteDireccionEntregas\ClienteDireccionEntregaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClienteDireccionEntregas extends ListRecords
{
    protected static string $resource = ClienteDireccionEntregaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
