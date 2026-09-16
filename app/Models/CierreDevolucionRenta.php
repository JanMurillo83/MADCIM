<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreDevolucionRenta extends Model
{
    protected $table = 'cierres_devolucion_renta';

    protected $fillable = [
        'cliente_id',
        'direccion_entrega_id',
        'estatus',
        'deposito_acumulado',
        'deposito_aplicado',
        'deposito_a_devolver',
        'total_faltantes',
        'saldo_por_cobrar',
        'nota_venta_venta_id',
        'devolucion_renta_id',
        'caja_movimiento_id',
        'observaciones',
        'user_id',
        'cerrada_en',
    ];

    protected $casts = [
        'deposito_acumulado' => 'decimal:2',
        'deposito_aplicado' => 'decimal:2',
        'deposito_a_devolver' => 'decimal:2',
        'total_faltantes' => 'decimal:2',
        'saldo_por_cobrar' => 'decimal:2',
        'cerrada_en' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function direccionEntrega(): BelongsTo
    {
        return $this->belongsTo(ClienteDireccionEntrega::class, 'direccion_entrega_id');
    }

    public function notaVentaVenta(): BelongsTo
    {
        return $this->belongsTo(NotasVentaVenta::class, 'nota_venta_venta_id');
    }

    public function devolucionRenta(): BelongsTo
    {
        return $this->belongsTo(DevolucionesRenta::class, 'devolucion_renta_id');
    }

    public function cajaMovimiento(): BelongsTo
    {
        return $this->belongsTo(CajaMovimiento::class, 'caja_movimiento_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
