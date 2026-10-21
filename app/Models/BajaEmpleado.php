<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class BajaEmpleado extends Model
{
    use BelongsToSucursalScope;

    protected $table = 'bajas_empleados';

    protected $fillable = [
        'empleado_id',
        'fecha_baja',
        'tipo',
        'motivo',
        'observaciones',
        'registrado_por_id',
    ];

    protected function casts(): array
    {
        return ['fecha_baja' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $baja): void {
            $baja->registrado_por_id ??= Auth::id();
        });
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
