<?php

namespace App\Filament\Resources\ClienteDireccionEntregas\Pages;

use App\Filament\Resources\ClienteDireccionEntregas\ClienteDireccionEntregaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClienteDireccionEntrega extends EditRecord
{
    protected static string $resource = ClienteDireccionEntregaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
