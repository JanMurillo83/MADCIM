<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisicionCompraPartida extends Model
{
    protected $table = 'requisicion_compra_partidas';

    protected $fillable = [
        'requisicion_compra_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'impuestos',
        'total',
    ];

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(RequisicionCompra::class, 'requisicion_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }
}
