<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroRenta extends Model
{
    protected $fillable = [
        'nota_venta_renta_id',
        'cliente_id',
        'cliente_nombre',
        'cliente_contacto',
        'cliente_telefono',
        'cliente_direccion',
        'producto_id',
        'cantidad',
        'dias_renta',
        'fecha_renta',
        'fecha_vencimiento',
        'importe_renta',
        'importe_deposito',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_renta' => 'date',
        'fecha_vencimiento' => 'date',
        'importe_renta' => 'decimal:2',
        'importe_deposito' => 'decimal:2',
    ];

    /**
     * Relación con NotasVentaRenta
     */
    public function notaVentaRenta(): BelongsTo
    {
        return $this->belongsTo(NotasVentaRenta::class, 'nota_venta_renta_id');
    }

    /**
     * Relación con Cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    /**
     * Relación con Productos
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }
}
