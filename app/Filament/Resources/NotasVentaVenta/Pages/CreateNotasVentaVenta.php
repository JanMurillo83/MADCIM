<?php

namespace App\Filament\Resources\NotasVentaVenta\Pages;

use App\Filament\Resources\NotasVentaVenta\NotasVentaVentaResource;
use App\Models\Productos;
use Filament\Resources\Pages\CreateRecord;

class CreateNotasVentaVenta extends CreateRecord
{
    protected static string $resource = NotasVentaVentaResource::class;

    protected function afterCreate(): void
    {
        // Disminuir existencia de productos
        foreach ($this->record->partidas as $partida) {
            $producto = Productos::find($partida->item);
            if ($producto) {
                $producto->decrement('existencia', $partida->cantidad);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
