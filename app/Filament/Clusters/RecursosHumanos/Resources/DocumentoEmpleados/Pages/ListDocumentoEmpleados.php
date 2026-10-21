<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\DocumentoEmpleadoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentoEmpleados extends ListRecords
{
    protected static string $resource = DocumentoEmpleadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
