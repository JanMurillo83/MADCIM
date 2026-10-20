<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Horarios\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\Horarios\HorarioResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHorario extends EditRecord
{
    protected static string $resource = HorarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
