<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmbarqueItem extends Model
{
    protected $table = 'embarque_items';

    protected $fillable = [
        'embarque_id',
        'documento_tipo',
        'documento_id',
        'cantidad_programada',
        'entregado',
        'fecha_entrega_real',
        'evidencia_url',
        'recibido_por',
        'observaciones_entrega',
    ];

    protected $casts = [
        'fecha_entrega_real' => 'datetime',
        'cantidad_programada' => 'decimal:2',
        'entregado' => 'boolean',
    ];

    public function embarque(): BelongsTo
    {
        return $this->belongsTo(Embarque::class, 'embarque_id');
    }
}
