<?php

namespace App\Filament\Resources\FacturasCfdi\Pages;

use App\Filament\Resources\FacturasCfdi\FacturasCfdiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFacturasCfdi extends CreateRecord
{
    protected static string $resource = FacturasCfdiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['saldo_pendiente'] = (float) ($data['total'] ?? 0);
        $data['user_id'] = auth()->id();

        if (!(auth()->user()?->isAdmin() ?? false)) {
            $data['sucursal_id'] = auth()->user()?->sucursal_id;
        }

        return $data;
    }
}
