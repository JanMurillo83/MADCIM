<?php

namespace App\Models;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoIncidencia;
use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use LogicException;

class Incidencia extends Model
{
    use BelongsToSucursalScope;

    protected $fillable = [
        'empleado_id',
        'sucursal_id',
        'checada_id',
        'tipo',
        'estatus',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'minutos',
        'dias',
        'motivo',
        'observaciones',
        'afecta_nomina',
        'solicitada_por_id',
        'autorizada_por_id',
        'autorizada_en',
        'comentario_autorizacion',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoIncidencia::class,
            'estatus' => EstadoIncidencia::class,
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'afecta_nomina' => 'boolean',
            'autorizada_en' => 'datetime',
            'dias' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $incidencia): void {
            $empleado = Empleado::query()
                ->withoutGlobalScope('sucursal')
                ->find($incidencia->empleado_id);

            if (!$empleado || (int) $empleado->sucursal_id !== (int) $incidencia->sucursal_id) {
                throw ValidationException::withMessages([
                    'sucursal_id' => 'La incidencia debe pertenecer a la sucursal del empleado.',
                ]);
            }

            $incidencia->estatus ??= EstadoIncidencia::Pendiente;
            $incidencia->solicitada_por_id ??= auth()->id();
        });

        static::updating(function (self $incidencia): void {
            if ($incidencia->getRawOriginal('estatus') !== EstadoIncidencia::Pendiente->value) {
                throw new LogicException('Las incidencias aprobadas o rechazadas no se pueden modificar.');
            }
        });
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function checada(): BelongsTo
    {
        return $this->belongsTo(Checada::class);
    }

    public function solicitadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitada_por_id');
    }

    public function autorizadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizada_por_id');
    }
}
