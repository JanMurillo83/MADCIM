<?php

namespace App\Models;

use App\Models\NotaDevolucionRenta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaDevolucionRentaPartida extends Model
{
    protected $table = 'nota_devolucion_renta_partidas';

    protected $fillable = [
        'nota_devolucion_renta_id',
        'nota_envio_partida_id',
        'producto_id',
        'descripcion',
        'cantidad_programada',
        'cantidad_recogida',
        'cantidad_aplicada',
        'observaciones',
    ];

    public function notaDevolucionRenta(): BelongsTo
    {
        return $this->belongsTo(NotaDevolucionRenta::class, 'nota_devolucion_renta_id');
    }

    public function notaEnvioPartida(): BelongsTo
    {
        return $this->belongsTo(NotaEnvioPartida::class, 'nota_envio_partida_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }
}
