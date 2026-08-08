<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\Productos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventarioMovimientoService
{
    /**
     * Registra un movimiento de inventario y actualiza la existencia del producto.
     *
     * @param  int  $productoId
     * @param  'entrada'|'salida'|'ajuste'  $tipo
     * @param  float  $cantidad  Cantidad positiva del movimiento
     * @param  string  $motivo
     * @param  string|null  $documentoReferencia
     * @return MovimientoInventario
     *
     * @throws \InvalidArgumentException
     */
    public static function registrar(int $productoId, string $tipo, float $cantidad, string $motivo, ?string $documentoReferencia = null): MovimientoInventario
    {
        if (! in_array($tipo, ['entrada', 'salida', 'ajuste'])) {
            throw new \InvalidArgumentException('Tipo de movimiento no válido.');
        }

        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($productoId, $tipo, $cantidad, $motivo, $documentoReferencia) {
            $producto = Productos::lockForUpdate()->findOrFail($productoId);
            $existenciaAntes = (float) $producto->existencia;

            $existenciaDespues = match ($tipo) {
                'entrada' => $existenciaAntes + $cantidad,
                'salida' => $existenciaAntes - $cantidad,
                'ajuste' => $cantidad,
            };

            if ($tipo === 'salida' && $existenciaDespues < 0) {
                throw new \InvalidArgumentException("No hay existencia suficiente para el producto {$producto->clave}.");
            }

            $movimiento = MovimientoInventario::create([
                'producto_id' => $productoId,
                'user_id' => Auth::id(),
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'existencia_antes' => $existenciaAntes,
                'existencia_despues' => $existenciaDespues,
                'motivo' => $motivo,
                'documento_referencia' => $documentoReferencia,
                'fecha_movimiento' => now(),
            ]);

            $producto->existencia = $existenciaDespues;
            $producto->save();

            return $movimiento;
        });
    }

    /**
     * Registra una salida de inventario.
     */
    public static function salida(int $productoId, float $cantidad, string $motivo, ?string $documentoReferencia = null): MovimientoInventario
    {
        return self::registrar($productoId, 'salida', $cantidad, $motivo, $documentoReferencia);
    }

    /**
     * Registra una entrada de inventario.
     */
    public static function entrada(int $productoId, float $cantidad, string $motivo, ?string $documentoReferencia = null): MovimientoInventario
    {
        return self::registrar($productoId, 'entrada', $cantidad, $motivo, $documentoReferencia);
    }
}
