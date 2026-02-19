<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevolucionRentaPartidas extends Model
{
    protected $fillable = [
        'devolucion_renta_id',
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
        return $this->belongsTo(DevolucionesRenta::class, 'devolucion_renta_id');
    }
}
