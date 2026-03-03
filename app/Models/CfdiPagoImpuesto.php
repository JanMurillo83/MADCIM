<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfdiPagoImpuesto extends Model
{
    protected $table = 'cfdi_pago_impuestos';

    protected $fillable = [
        'pago_docto_id',
        'tipo',
        'impuesto',
        'tipo_factor',
        'tasa_o_cuota',
        'base',
        'importe',
    ];

    public function pagoDocto(): BelongsTo
    {
        return $this->belongsTo(CfdiPagoDocto::class, 'pago_docto_id');
    }
}
