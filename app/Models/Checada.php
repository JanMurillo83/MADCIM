<?php

namespace App\Models;

use App\Enums\MetodoChecada;
use App\Enums\TipoChecada;
use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Illuminate\Database\Eloquent\Model;

class Checada extends Model
{
    use BelongsToSucursalScope;

    protected $fillable = [
        'empleado_id',
        'sucursal_id',
        'registrada_en',
        'tipo',
        'metodo',
        'dispositivo',
        'identificador_externo',
        'autorizada_por_id',
        'motivo_autorizacion',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'registrada_en' => 'datetime',
            'tipo' => TipoChecada::class,
            'metodo' => MetodoChecada::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Las checadas originales no se pueden modificar.');
        });

        static::deleting(function (): void {
            throw new LogicException('Las checadas originales no se pueden eliminar.');
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

    public function autorizadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizada_por_id');
    }
}
