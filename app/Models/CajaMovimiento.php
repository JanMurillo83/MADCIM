<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CajaMovimiento extends Model
{
    use BelongsToSucursalScope;

    protected $table = 'caja_movimientos';

    protected $fillable = [
        'caja_id',
        'corte_id',
        'tipo',
        'fuente',
        'metodo_pago',
        'importe',
        'referencia',
        'observaciones',
        'user_id',
        'fecha',
        'movimentable_type',
        'movimentable_id',
        'aplicado_a_caja',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'importe' => 'decimal:2',
        'aplicado_a_caja' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $movimiento): void {
            if ($movimiento->corte_id || ! $movimiento->caja_id) {
                return;
            }

            $movimiento->corte_id = Caja::query()
                ->whereKey($movimiento->caja_id)
                ->value('corte_id');
        });
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimentable(): MorphTo
    {
        return $this->morphTo();
    }
}
