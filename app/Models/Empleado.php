<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use BelongsToSucursalScope;

    protected $fillable = [
        'numero_empleado',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'sucursal_id',
        'horario_id',
        'departamento',
        'puesto',
        'fecha_ingreso',
        'tipo_contrato',
        'sueldo_diario',
        'sueldo_semanal',
        'estatus',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'sueldo_diario' => 'decimal:2',
            'sueldo_semanal' => 'decimal:2',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return collect([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])->filter()->implode(' ');
    }
}
