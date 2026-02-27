<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    use BelongsToSucursalScope;
    protected $table = 'cajas';

    protected $fillable = [
        'nombre',
        'sucursal_id',
        'fecha_apertura',
        'usuario_apertura_id',
        'saldo_inicial_cash',
        'estatus',
        'fecha_cierre',
        'usuario_cierre_id',
        'total_ingresos_cash',
        'total_egresos_cash',
        'total_diferencia',
        'observaciones_cierre',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'saldo_inicial_cash' => 'decimal:2',
        'total_ingresos_cash' => 'decimal:2',
        'total_egresos_cash' => 'decimal:2',
        'total_diferencia' => 'decimal:2',
    ];

    public function movimientos(): HasMany
    {
        return $this->hasMany(CajaMovimiento::class, 'caja_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function usuarioApertura(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_apertura_id');
    }

    public function usuarioCierre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cierre_id');
    }
}
