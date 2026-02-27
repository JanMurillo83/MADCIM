<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraPartida extends Model
{
    protected $table = 'orden_compra_partidas';

    protected $fillable = [
        'orden_compra_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'impuestos',
        'total',
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }
}
