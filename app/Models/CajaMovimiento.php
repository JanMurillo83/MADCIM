<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CajaMovimiento extends Model
{
    protected $table = 'caja_movimientos';

    protected $fillable = [
        'caja_id',
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
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'importe' => 'decimal:2',
    ];

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
