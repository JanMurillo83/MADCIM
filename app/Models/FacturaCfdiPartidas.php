<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FacturaCfdiPartidas extends Model
{
    protected $fillable = [
        'factura_cfdi_id',
        'cantidad',
        'item',
        'clave_prod_serv',
        'no_identificacion',
        'clave_unidad',
        'unidad',
        'descripcion',
        'objeto_imp',
        'valor_unitario',
        'subtotal',
        'descuento',
        'impuestos',
        'total',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(FacturasCfdi::class, 'factura_cfdi_id');
    }

    public function impuestos(): MorphMany
    {
        return $this->morphMany(CfdiPartidaImpuesto::class, 'partida');
    }
}
