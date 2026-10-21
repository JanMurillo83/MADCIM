<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DocumentoEmpleado extends Model
{
    use BelongsToSucursalScope;

    protected $table = 'documento_empleados';

    protected $fillable = [
        'empleado_id',
        'tipo',
        'nombre',
        'fecha_emision',
        'fecha_vencimiento',
        'archivo',
        'dias_alerta',
        'estatus',
        'observaciones',
        'registrado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'dias_alerta' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $documento): void {
            $documento->registrado_por_id ??= Auth::id();
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
