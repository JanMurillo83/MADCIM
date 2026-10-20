<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Checadas\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\Checadas\ChecadaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChecada extends EditRecord
{
    protected static string $resource = ChecadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
