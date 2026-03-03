<?php

namespace App\Filament\Resources\Configuracion\Pages;

use App\Filament\Resources\Configuracion\ConfiguracionResource;
use Filament\Resources\Pages\EditRecord;

class EditConfiguracion extends EditRecord
{
    protected static string $resource = ConfiguracionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
