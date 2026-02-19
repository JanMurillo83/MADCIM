<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaVentaVentaPartidas extends Model
{
    protected $fillable = [
        'nota_venta_venta_id',
        'cantidad',
        'item',
        'descripcion',
        'valor_unitario',
        'subtotal',
        'impuestos',
        'total',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(NotasVentaVenta::class, 'nota_venta_venta_id');
    }
}
