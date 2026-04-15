<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentoSerieFolio;
use App\Models\NotaDevolucionRentaPartida;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class NotaDevolucionRenta extends Model
{
    use HasDocumentoSerieFolio;

    protected $table = 'notas_devolucion_renta';

    protected $fillable = [
        'serie',
        'folio',
        'nota_envio_id',
        'nota_venta_renta_id',
        'cliente_id',
        'fecha_emision',
        'estatus',
        'observaciones',
        'aplicada_en',
        'user_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'aplicada_en' => 'datetime',
    ];

    public function notaEnvio(): BelongsTo
    {
        return $this->belongsTo(NotaEnvio::class, 'nota_envio_id');
    }

    public function notaOrigen(): BelongsTo
    {
        return $this->belongsTo(NotasVentaRenta::class, 'nota_venta_renta_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(NotaDevolucionRentaPartida::class, 'nota_devolucion_renta_id');
    }

    public function aplicarCantidadesRecogidas(): void
    {
        $this->loadMissing(['partidas', 'notaEnvio.partidas', 'notaEnvio.notaVentaRenta']);

        DB::transaction(function () {
            foreach ($this->partidas as $partida) {
                if (!$partida->nota_envio_partida_id) {
                    continue;
                }

                $cantidadObjetivo = (float) $partida->cantidad_recogida;
                $cantidadProgramada = (float) $partida->cantidad_programada;
                if ($cantidadObjetivo < 0) {
                    $cantidadObjetivo = 0;
                }
                if ($cantidadObjetivo > $cantidadProgramada) {
                    $cantidadObjetivo = $cantidadProgramada;
                }

                if ((float) $partida->cantidad_recogida !== $cantidadObjetivo) {
                    $partida->update([
                        'cantidad_recogida' => $cantidadObjetivo,
                    ]);
                }

                $cantidadAplicada = (float) $partida->cantidad_aplicada;
                $delta = $cantidadObjetivo - $cantidadAplicada;

                if (abs($delta) < 0.00001) {
                    continue;
                }

                $partidaEnvio = NotaEnvioPartida::query()
                    ->whereKey($partida->nota_envio_partida_id)
                    ->lockForUpdate()
                    ->first();

                if (!$partidaEnvio) {
                    continue;
                }

                $cantidadMaxima = (float) $partidaEnvio->cantidad;
                $cantidadDevueltaActual = (float) $partidaEnvio->cantidad_devuelta;
                $nuevaCantidadDevuelta = $cantidadDevueltaActual + $delta;

                if ($nuevaCantidadDevuelta < 0) {
                    $nuevaCantidadDevuelta = 0;
                }

                if ($nuevaCantidadDevuelta > $cantidadMaxima) {
                    $nuevaCantidadDevuelta = $cantidadMaxima;
                }

                $partidaEnvio->update([
                    'cantidad_devuelta' => $nuevaCantidadDevuelta,
                    'estado' => $nuevaCantidadDevuelta >= $cantidadMaxima ? 'Devuelto' : 'Activo',
                ]);

                $partida->update([
                    'cantidad_aplicada' => $cantidadObjetivo,
                ]);
            }

            $notaEnvio = $this->notaEnvio;
            if ($notaEnvio) {
                $estadoNotaEnvio = $this->calcularEstadoDesdePartidas(
                    $notaEnvio->partidas,
                    static fn (NotaEnvioPartida $p): float => (float) $p->cantidad,
                    static fn (NotaEnvioPartida $p): float => (float) $p->cantidad_devuelta,
                );

                $notaEnvio->update(['estado_renta' => $estadoNotaEnvio]);

                if ($notaEnvio->nota_venta_renta_id && $estadoNotaEnvio === 'Devuelta') {
                    $pendientesEnNota = NotaEnvio::query()
                        ->where('nota_venta_renta_id', $notaEnvio->nota_venta_renta_id)
                        ->where('estado_renta', '!=', 'Devuelta')
                        ->count();

                    if ($pendientesEnNota === 0) {
                        $notaEnvio->notaVentaRenta?->update(['estatus' => 'Devuelta']);
                    }
                }
            }

            $estadoNota = $this->calcularEstadoDesdePartidas(
                $this->partidas,
                static fn (NotaDevolucionRentaPartida $p): float => (float) $p->cantidad_programada,
                static fn (NotaDevolucionRentaPartida $p): float => (float) $p->cantidad_aplicada,
            );

            $this->forceFill([
                'estatus' => $estadoNota,
                'aplicada_en' => now(),
            ])->saveQuietly();
        });
    }

    public function cancelar(): void
    {
        $this->loadMissing(['partidas', 'notaEnvio.partidas', 'notaEnvio.notaVentaRenta']);

        DB::transaction(function () {
            if (in_array($this->estatus, ['Cancelada', 'Devuelta'], true)) {
                return;
            }

            if (in_array($this->estatus, ['Aplicada', 'Parcial', 'Devuelta'], true)) {
                foreach ($this->partidas as $partida) {
                    if (!$partida->nota_envio_partida_id) {
                        continue;
                    }

                    $aplicada = (float) $partida->cantidad_aplicada;
                    if ($aplicada <= 0) {
                        continue;
                    }

                    $partidaEnvio = NotaEnvioPartida::query()
                        ->whereKey($partida->nota_envio_partida_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$partidaEnvio) {
                        continue;
                    }

                    $nuevaCantidadDevuelta = (float) $partidaEnvio->cantidad_devuelta - $aplicada;
                    if ($nuevaCantidadDevuelta < 0) {
                        $nuevaCantidadDevuelta = 0;
                    }

                    $cantidadMaxima = (float) $partidaEnvio->cantidad;
                    if ($nuevaCantidadDevuelta > $cantidadMaxima) {
                        $nuevaCantidadDevuelta = $cantidadMaxima;
                    }

                    $partidaEnvio->update([
                        'cantidad_devuelta' => $nuevaCantidadDevuelta,
                        'estado' => $nuevaCantidadDevuelta >= $cantidadMaxima ? 'Devuelto' : 'Activo',
                    ]);

                    $partida->update([
                        'cantidad_aplicada' => 0,
                    ]);
                }
            }

            $notaEnvio = $this->notaEnvio;
            if ($notaEnvio) {
                $estadoNotaEnvio = $this->calcularEstadoDesdePartidas(
                    $notaEnvio->partidas,
                    static fn (NotaEnvioPartida $p): float => (float) $p->cantidad,
                    static fn (NotaEnvioPartida $p): float => (float) $p->cantidad_devuelta,
                );

                $notaEnvio->update(['estado_renta' => $estadoNotaEnvio]);

                if ($notaEnvio->nota_venta_renta_id && $notaEnvio->notaVentaRenta) {
                    $pendientesEnNota = NotaEnvio::query()
                        ->where('nota_venta_renta_id', $notaEnvio->nota_venta_renta_id)
                        ->where('estado_renta', '!=', 'Devuelta')
                        ->count();

                    $notaEnvio->notaVentaRenta->update([
                        'estatus' => $pendientesEnNota === 0 ? 'Devuelta' : 'Activa',
                    ]);
                }
            }

            $this->forceFill([
                'estatus' => 'Cancelada',
            ])->saveQuietly();
        });
    }

    private function calcularEstadoDesdePartidas(iterable $partidas, callable $programada, callable $real): string
    {
        $hayProgramadas = false;
        $todasCero = true;
        $todasCompletas = true;

        foreach ($partidas as $partida) {
            $cantidadProgramada = max(0, (float) $programada($partida));
            $cantidadReal = max(0, (float) $real($partida));

            if ($cantidadProgramada <= 0) {
                continue;
            }

            $hayProgramadas = true;

            if ($cantidadReal > 0) {
                $todasCero = false;
            }

            if ($cantidadReal + 0.00001 < $cantidadProgramada) {
                $todasCompletas = false;
            }
        }

        if (!$hayProgramadas || $todasCero) {
            return 'Pendiente';
        }

        if ($todasCompletas) {
            return 'Devuelta';
        }

        return 'Parcial';
    }
}
