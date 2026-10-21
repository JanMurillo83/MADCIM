<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class MovimientoLaboral extends Model
{
    use BelongsToSucursalScope;

    protected $table = 'movimientos_laborales';


    protected $fillable = [
        'empleado_id', 'fecha_movimiento', 'tipo', 'sucursal_origen_id',
        'sucursal_destino_id', 'departamento', 'puesto', 'horario_id',
        'motivo', 'registrado_por_id',
    ];

    protected function casts(): array
    {
        return ['fecha_movimiento' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $registro) => $registro->registrado_por_id ??= Auth::id());
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function sucursalOrigen(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_origen_id');
    }

    public function sucursalDestino(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_destino_id');
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class);
    }
}
