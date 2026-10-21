<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\IncidenciaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIncidencia extends EditRecord
{
    protected static string $resource = IncidenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
