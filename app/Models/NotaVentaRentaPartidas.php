<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaVentaRentaPartidas extends Model
{
    protected $fillable = [
        'nota_venta_renta_id',
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
        return $this->belongsTo(NotasVentaRenta::class, 'nota_venta_renta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'item');
    }
}
