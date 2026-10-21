<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class HistorialSalarial extends Model
{
    use BelongsToSucursalScope;

    protected $table = 'historial_salarial';

    protected $fillable = [
        'empleado_id', 'fecha_inicio', 'fecha_fin', 'sueldo_diario',
        'sueldo_semanal', 'motivo', 'registrado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'sueldo_diario' => 'decimal:2',
            'sueldo_semanal' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $registro) => $registro->registrado_por_id ??= Auth::id());
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
