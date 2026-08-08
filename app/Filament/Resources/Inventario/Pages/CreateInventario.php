<?php

namespace App\Filament\Resources\Inventario\Pages;

use App\Filament\Resources\Inventario\InventarioResource;
use App\Models\Productos;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateInventario extends CreateRecord
{
    protected static string $resource = InventarioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $producto = Productos::lockForUpdate()->findOrFail($data['producto_id']);
        $tipo = $data['tipo'];
        $cantidad = (float) ($data['cantidad'] ?? 0);

        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $existenciaAntes = (float) $producto->existencia;
        $existenciaDespues = match ($tipo) {
            'entrada' => $existenciaAntes + $cantidad,
            'salida' => $existenciaAntes - $cantidad,
            'ajuste' => $cantidad,
            default => $existenciaAntes,
        };

        if ($tipo === 'salida' && $existenciaDespues < 0) {
            throw new \InvalidArgumentException('No hay existencia suficiente para realizar la salida.');
        }

        $data['existencia_antes'] = $existenciaAntes;
        $data['existencia_despues'] = $existenciaDespues;
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        DB::transaction(function () {
            $movimiento = $this->record;
            $producto = Productos::lockForUpdate()->findOrFail($movimiento->producto_id);
            $producto->existencia = $movimiento->existencia_despues;
            $producto->save();
        });
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
