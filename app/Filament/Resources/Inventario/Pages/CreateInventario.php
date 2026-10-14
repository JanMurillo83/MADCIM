<?php

namespace App\Filament\Resources\Inventario\Pages;

use App\Filament\Resources\Inventario\InventarioResource;
use App\Models\Productos;
use App\Services\ExistenciaSucursalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateInventario extends CreateRecord
{
    protected static string $resource = InventarioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $sucursalId = ExistenciaSucursalService::sucursalActual($data['sucursal_id'] ?? null);
        $existenciaAntes = ExistenciaSucursalService::obtener((int) $data['producto_id'], $sucursalId);
        $cantidad = (float) ($data['cantidad'] ?? 0);
        $existenciaDespues = match ($data['tipo']) {
            'entrada' => $existenciaAntes + $cantidad,
            'salida' => $existenciaAntes - $cantidad,
            'ajuste' => $cantidad,
            default => $existenciaAntes,
        };

        if ($cantidad <= 0 || ($data['tipo'] === 'salida' && $existenciaDespues < 0)) {
            throw new \InvalidArgumentException('La cantidad o existencia de la sucursal no es válida.');
        }

        $data['sucursal_id'] = $sucursalId;
        $data['existencia_antes'] = $existenciaAntes;
        $data['existencia_despues'] = $existenciaDespues;
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $movimiento = $this->record;
        ExistenciaSucursalService::ajustar(
            (int) $movimiento->producto_id,
            (int) $movimiento->sucursal_id,
            (float) $movimiento->existencia_despues,
        );
        $producto = Productos::findOrFail($movimiento->producto_id);
        $producto->update([
            'existencia' => (float) \App\Models\ExistenciaSucursal::query()
                ->where('producto_id', $producto->id)
                ->sum('existencia'),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return InventarioResource::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
