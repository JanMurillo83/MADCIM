<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Embarque extends Model
{
    protected $table = 'embarques';

    protected $fillable = [
        'folio',
        'fecha_programada',
        'vehiculo',
        'chofer_id',
        'estatus',
        'cliente_id',
        'direccion_entrega_id',
        'observaciones',
        'user_id_creador',
    ];

    protected $casts = [
        'fecha_programada' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(EmbarqueItem::class, 'embarque_id');
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chofer_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function direccionEntrega(): BelongsTo
    {
        return $this->belongsTo(ClienteDireccionEntrega::class, 'direccion_entrega_id');
    }
}
