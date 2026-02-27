<?php

namespace App\Filament\Resources\NotasEnvio\Pages;

use App\Filament\Resources\NotasEnvio\NotasEnvioResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotasEnvio extends EditRecord
{
    protected static string $resource = NotasEnvioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
