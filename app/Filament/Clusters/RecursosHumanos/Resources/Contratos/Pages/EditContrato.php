<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Contratos\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\Contratos\ContratoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContrato extends EditRecord
{
    protected static string $resource = ContratoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
