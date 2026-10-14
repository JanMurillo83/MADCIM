<?php

namespace App\Services;

use App\Models\ExistenciaSucursal;
use App\Models\Productos;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExistenciaSucursalService
{
    public static function sucursalActual(?int $sucursalId = null): int
    {
        $sucursalId ??= Auth::user()?->sucursal_id;
        $sucursalId ??= Sucursal::query()->orderBy('id')->value('id');

        if (! $sucursalId) {
            throw new \RuntimeException('No hay una sucursal disponible para controlar la existencia.');
        }

        return (int) $sucursalId;
    }

    public static function obtener(int $productoId, ?int $sucursalId = null): float
    {
        return (float) ExistenciaSucursal::query()
            ->where('producto_id', $productoId)
            ->where('sucursal_id', self::sucursalActual($sucursalId))
            ->value('existencia');
    }

    public static function ajustar(int $productoId, int $sucursalId, float $existencia): ExistenciaSucursal
    {
        return DB::transaction(function () use ($productoId, $sucursalId, $existencia): ExistenciaSucursal {
            $stock = ExistenciaSucursal::query()
                ->where('producto_id', $productoId)
                ->where('sucursal_id', $sucursalId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = new ExistenciaSucursal([
                    'producto_id' => $productoId,
                    'sucursal_id' => $sucursalId,
                ]);
            }

            $stock->existencia = $existencia;
            $stock->save();

            return $stock;
        });
    }

    public static function cambiar(int $productoId, int $sucursalId, float $delta): ExistenciaSucursal
    {
        $stock = ExistenciaSucursal::query()
            ->where('producto_id', $productoId)
            ->where('sucursal_id', $sucursalId)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            $stock = ExistenciaSucursal::create([
                'producto_id' => $productoId,
                'sucursal_id' => $sucursalId,
                'existencia' => 0,
            ]);
        }

        $nuevaExistencia = (float) $stock->existencia + $delta;
        if ($nuevaExistencia < 0) {
            $producto = Productos::find($productoId);
            throw new \InvalidArgumentException("No hay existencia suficiente para el producto {$producto?->clave}.");
        }

        $stock->update(['existencia' => $nuevaExistencia]);

        return $stock;
    }
}
