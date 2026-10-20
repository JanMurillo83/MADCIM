<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaCorte extends Model
{
    protected $table = 'caja_cortes';

    protected $fillable = [
        'corte_id',
        'caja_id',
        'sucursal_id',
        'fecha_apertura',
        'fecha_cierre',
        'usuario_apertura_id',
        'usuario_cierre_id',
        'saldo_inicial_cash',
        'total_ingresos_cash',
        'total_egresos_cash',
        'efectivo_teorico',
        'efectivo_contado',
        'total_diferencia',
        'denominaciones_efectivo',
        'observaciones_cierre',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'saldo_inicial_cash' => 'decimal:2',
        'total_ingresos_cash' => 'decimal:2',
        'total_egresos_cash' => 'decimal:2',
        'efectivo_teorico' => 'decimal:2',
        'efectivo_contado' => 'decimal:2',
        'total_diferencia' => 'decimal:2',
        'denominaciones_efectivo' => 'array',
    ];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function usuarioApertura(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_apertura_id');
    }

    public function usuarioCierre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cierre_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(CajaMovimiento::class, 'corte_id', 'corte_id');
    }
}
