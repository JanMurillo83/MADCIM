<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionPartidas extends Model
{
    protected $fillable = [
        'cotizacion_id',
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
        return $this->belongsTo(Cotizaciones::class, 'cotizacion_id');
    }
}
