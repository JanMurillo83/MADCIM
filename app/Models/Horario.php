<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    use BelongsToSucursalScope;

    protected $fillable = [
        'nombre',
        'sucursal_id',
        'hora_entrada',
        'hora_salida_comida',
        'hora_regreso_comida',
        'hora_salida',
        'tolerancia_entrada',
        'tolerancia_regreso_comida',
        'duracion_comida',
        'dia_descanso',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'dia_descanso' => 'integer',
            'tolerancia_entrada' => 'integer',
            'tolerancia_regreso_comida' => 'integer',
            'duracion_comida' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class);
    }
}
