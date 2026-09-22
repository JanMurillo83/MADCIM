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
        'tipo_nota_renta',
        'tipo_renta',
        'duracion_renta',
        'dias_renta',
        'fecha_vencimiento',
        'metros_m2',
        'descripcion',
        'valor_unitario',
        'subtotal',
        'impuestos',
        'total',
        'deposito',
    ];

    protected $casts = [
        'cantidad' => 'decimal:8',
        'duracion_renta' => 'integer',
        'dias_renta' => 'integer',
        'fecha_vencimiento' => 'date',
        'metros_m2' => 'decimal:2',
        'valor_unitario' => 'decimal:8',
        'subtotal' => 'decimal:8',
        'impuestos' => 'decimal:8',
        'total' => 'decimal:8',
        'deposito' => 'decimal:8',
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
