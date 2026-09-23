<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPreciosMadera extends Model
{
    protected $table = 'historial_precios_madera';

    protected $fillable = [
        'lote_id',
        'producto_id',
        'producto_base_id',
        'usuario_id',
        'tipo',
        'largo_cm',
        'precio_renta_anterior',
        'precio_renta_nuevo',
        'precio_venta_anterior',
        'precio_venta_nuevo',
        'base_renta',
        'base_venta',
        'formula',
    ];

    protected $casts = [
        'largo_cm' => 'decimal:4',
        'precio_renta_anterior' => 'decimal:8',
        'precio_renta_nuevo' => 'decimal:8',
        'precio_venta_anterior' => 'decimal:8',
        'precio_venta_nuevo' => 'decimal:8',
        'base_renta' => 'decimal:8',
        'base_venta' => 'decimal:8',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

    public function productoBase(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_base_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
