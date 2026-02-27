<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecepcionCompraPartida extends Model
{
    protected $table = 'recepcion_compra_partidas';

    protected $fillable = [
        'recepcion_compra_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'impuestos',
        'total',
    ];

    protected static function booted(): void
    {
        static::created(function (self $partida) {
            $partida->ajustarExistencia((float) $partida->cantidad);
        });

        static::updated(function (self $partida) {
            $originalProductoId = $partida->getOriginal('producto_id');
            $originalCantidad = (float) $partida->getOriginal('cantidad');
            $nuevoProductoId = $partida->producto_id;
            $nuevaCantidad = (float) $partida->cantidad;

            if ($originalProductoId && $originalProductoId !== $nuevoProductoId) {
                Productos::where('id', $originalProductoId)->decrement('existencia', $originalCantidad);
                if ($nuevoProductoId) {
                    Productos::where('id', $nuevoProductoId)->increment('existencia', $nuevaCantidad);
                }
                return;
            }

            if ($nuevoProductoId) {
                $diff = $nuevaCantidad - $originalCantidad;
                if ($diff > 0) {
                    Productos::where('id', $nuevoProductoId)->increment('existencia', $diff);
                } elseif ($diff < 0) {
                    Productos::where('id', $nuevoProductoId)->decrement('existencia', abs($diff));
                }
            }
        });

        static::deleted(function (self $partida) {
            $partida->ajustarExistencia(-1 * (float) $partida->cantidad);
        });
    }

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(RecepcionCompra::class, 'recepcion_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

    private function ajustarExistencia(float $cantidad): void
    {
        if (!$this->producto_id || $cantidad == 0.0) {
            return;
        }

        if ($cantidad > 0) {
            Productos::where('id', $this->producto_id)->increment('existencia', $cantidad);
            return;
        }

        Productos::where('id', $this->producto_id)->decrement('existencia', abs($cantidad));
    }
}
