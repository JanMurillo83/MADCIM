<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaEnvioPartida extends Model
{
    protected $table = 'nota_envio_partidas';

    protected $fillable = [
        'nota_envio_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'cantidad_devuelta',
        'estado',
        'observaciones',
    ];

    public function notaEnvio(): BelongsTo
    {
        return $this->belongsTo(NotaEnvio::class, 'nota_envio_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }
}
