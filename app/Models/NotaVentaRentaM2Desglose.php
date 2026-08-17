<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaVentaRentaM2Desglose extends Model
{
    protected $table = 'nota_venta_renta_m2_desglose';

    protected $fillable = [
        'nota_venta_renta_id',
        'producto_id',
        'clave',
        'descripcion',
        'cantidad',
        'm2_cubre',
        'm2_total',
        'tipo_madera',
        'observaciones',
    ];

    public function notaVentaRenta(): BelongsTo
    {
        return $this->belongsTo(NotasVentaRenta::class, 'nota_venta_renta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }
}
