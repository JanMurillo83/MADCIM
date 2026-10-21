<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispositivoBiometrico extends Model
{
    protected $table = 'dispositivos_biometricos';

    protected $fillable = [
        'nombre',
        'numero_serie',
        'modelo',
        'sucursal_id',
        'token_hash',
        'activo',
        'ultima_conexion_en',
        'ultima_checada_en',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'ultima_conexion_en' => 'datetime',
            'ultima_checada_en' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(EmpleadoDispositivoId::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoAdms::class);
    }
}
