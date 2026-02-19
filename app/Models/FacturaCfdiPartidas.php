<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaCfdiPartidas extends Model
{
    protected $fillable = [
        'factura_cfdi_id',
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
        return $this->belongsTo(FacturasCfdi::class, 'factura_cfdi_id');
    }
}
