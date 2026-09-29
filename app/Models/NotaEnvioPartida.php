<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NotaEnvioPartida extends Model
{
    protected $table = 'nota_envio_partidas';

    protected static function booted(): void
    {
        static::creating(function (self $partida): void {
            $envio = NotaEnvio::query()->find($partida->nota_envio_id);
            $partidaRenta = $partida->nota_venta_renta_partida_id
                ? NotaVentaRentaPartidas::query()->with('documento')->find($partida->nota_venta_renta_partida_id)
                : null;

            if ($envio && $partidaRenta && (int) $envio->nota_venta_renta_id !== (int) $partidaRenta->nota_venta_renta_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'partidas' => 'La partida seleccionada no pertenece a la Nota de Venta Renta del envío.',
                ]);
            }
        });
    }

    protected $fillable = [
        'nota_envio_id',
        'nota_venta_renta_partida_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'dias_renta',
        'fecha_vencimiento',
        'cantidad_devuelta',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'dias_renta' => 'integer',
        'fecha_vencimiento' => 'date',
    ];

    public function notaEnvio(): BelongsTo
    {
        return $this->belongsTo(NotaEnvio::class, 'nota_envio_id');
    }

    public function partidaRenta(): BelongsTo
    {
        return $this->belongsTo(NotaVentaRentaPartidas::class, 'nota_venta_renta_partida_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

    public function registroRenta(): HasOne
    {
        return $this->hasOne(RegistroRenta::class, 'nota_envio_partida_id');
    }
}
