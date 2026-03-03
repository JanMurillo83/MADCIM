<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CfdiPagoDocto extends Model
{
    protected $table = 'cfdi_pago_doctos';

    protected $fillable = [
        'pago_id',
        'documento_type',
        'documento_id',
        'uuid',
        'moneda_dr',
        'equivalencia_dr',
        'num_parcialidad',
        'imp_saldo_ant',
        'imp_pagado',
        'imp_saldo_insoluto',
        'objeto_imp_dr',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pagos::class, 'pago_id');
    }

    public function documento(): MorphTo
    {
        return $this->morphTo();
    }

    public function impuestos(): HasMany
    {
        return $this->hasMany(CfdiPagoImpuesto::class, 'pago_docto_id');
    }
}
