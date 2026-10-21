<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\DocumentoEmpleadoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentoEmpleado extends EditRecord
{
    protected static string $resource = DocumentoEmpleadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
