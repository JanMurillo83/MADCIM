<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotaEnvio extends Model
{
    protected $table = 'notas_envio';

    protected $fillable = [
        'serie',
        'folio',
        'nota_venta_renta_id',
        'nota_venta_venta_id',
        'cliente_id',
        'direccion_entrega_id',
        'fecha_emision',
        'dias_renta',
        'fecha_vencimiento',
        'observaciones',
        'estatus',
        'estado_renta',
        'user_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function notaVentaRenta(): BelongsTo
    {
        return $this->belongsTo(NotasVentaRenta::class, 'nota_venta_renta_id');
    }

    public function notaVentaVenta(): BelongsTo
    {
        return $this->belongsTo(NotasVentaVenta::class, 'nota_venta_venta_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function direccionEntrega(): BelongsTo
    {
        return $this->belongsTo(ClienteDireccionEntrega::class, 'direccion_entrega_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(NotaEnvioPartida::class, 'nota_envio_id');
    }
}
