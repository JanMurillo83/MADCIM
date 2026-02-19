<?php

namespace App\Filament\Resources\FacturasCfdi\Pages;

use App\Filament\Resources\FacturasCfdi\FacturasCfdiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFacturasCfdi extends EditRecord
{
    protected static string $resource = FacturasCfdiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
