<?php

namespace App\Filament\Resources\Inventario\Pages;

use App\Filament\Resources\Inventario\InventarioResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditInventario extends EditRecord
{
    protected static string $resource = InventarioResource::class;

    protected function getFormActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Cerrar')
                ->color('gray')
                ->url(InventarioResource::getUrl('index'))
                ->icon('heroicon-m-x-mark'),
        ];
    }
}
