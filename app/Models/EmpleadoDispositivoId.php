<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpleadoDispositivoId extends Model
{
    protected $table = 'empleado_dispositivo_ids';

    protected $fillable = ['empleado_id', 'dispositivo_biometrico_id', 'identificador'];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(DispositivoBiometrico::class, 'dispositivo_biometrico_id');
    }
}
