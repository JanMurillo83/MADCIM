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
        'existencia_antes',
        'costo_promedio_antes',
        'ultimo_costo_antes',
    ];

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(RecepcionCompra::class, 'recepcion_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

}
