<?php

namespace App\Filament\Resources\NotasVentaVenta\Pages;

use App\Filament\Resources\NotasVentaVenta\NotasVentaVentaResource;
use App\Models\Productos;
use App\Services\InventarioMovimientoService;
use Filament\Resources\Pages\CreateRecord;

class CreateNotasVentaVenta extends CreateRecord
{
    protected static string $resource = NotasVentaVentaResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        $referencia = $record->serie . $record->folio;

        foreach ($record->partidas as $partida) {
            $producto = Productos::find($partida->item);
            if (! $producto) {
                continue;
            }

            InventarioMovimientoService::salida(
                productoId: $producto->id,
                cantidad: (float) $partida->cantidad,
                motivo: "Venta generada en nota {$referencia}",
                documentoReferencia: $referencia
            );
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
