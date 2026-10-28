<?php

namespace App\Services;

use App\Enums\MetodoChecada;
use App\Enums\TipoChecada;
use App\Models\Checada;
use App\Models\Horario;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClasificarMarcacionesService
{
    /**
     * @return array{movimientos: array<string, Checada>, requiere_revision: bool}
     */
    public function clasificar(Collection $checadas, Horario $horario, Carbon $fechaJornada): array
    {
        $movimientos = [];
        $requiereRevision = false;

        foreach ($checadas->filter(fn (Checada $checada): bool => $checada->metodo !== MetodoChecada::Huella) as $checada) {
            $movimientos[$checada->tipo->value] ??= $checada;
        }

        $biometricas = $checadas
            ->filter(fn (Checada $checada): bool => $checada->metodo === MetodoChecada::Huella)
            ->sortBy('registrada_en')
            ->values();

        if ($biometricas->isEmpty()) {
            return ['movimientos' => $movimientos, 'requiere_revision' => false];
        }

        $salidaComidaProgramada = $horario->hora_salida_comida
            ? $this->momento($fechaJornada, $horario->hora_salida_comida)
            : null;
        $salidaProgramada = $horario->hora_salida
            ? $this->momento($fechaJornada, $horario->hora_salida)
            : null;
        $tolerancia = (int) $horario->tolerancia_entrada;

        $indice = 0;
        $primera = $biometricas->first();

        if (! $salidaComidaProgramada || $primera->registrada_en->lt($salidaComidaProgramada)) {
            $movimientos[TipoChecada::Entrada->value] ??= $primera;
            $indice = 1;
        } elseif ($salidaComidaProgramada && $primera->registrada_en->lt($salidaComidaProgramada->copy()->addMinutes($tolerancia))) {
            $requiereRevision = true;
        } else {
            $requiereRevision = true;
        }

        $pendientes = $biometricas->slice($indice)->values();

        if ($salidaComidaProgramada && $pendientes->isNotEmpty()) {
            $salidaComida = $pendientes->first();
            $ventanaSalida = $salidaComidaProgramada->copy()->subMinutes($tolerancia);

            if ($salidaComida->registrada_en->gte($ventanaSalida)) {
                $movimientos[TipoChecada::SalidaComida->value] = $salidaComida;
                $pendientes = $pendientes->slice(1)->values();

                if ($pendientes->isNotEmpty()) {
                    $regreso = $pendientes->first();
                    if (! $salidaProgramada || $regreso->registrada_en->lt($salidaProgramada)) {
                        $movimientos[TipoChecada::RegresoComida->value] = $regreso;
                        $pendientes = $pendientes->slice(1)->values();
                    } else {
                        $requiereRevision = true;
                    }
                }
            }
        }

        if ($pendientes->isNotEmpty()) {
            $movimientos[TipoChecada::Salida->value] = $pendientes->last();

            if ($pendientes->count() > 1) {
                $requiereRevision = true;
            }
        }

        return [
            'movimientos' => $movimientos,
            'requiere_revision' => $requiereRevision,
        ];
    }

    private function momento(Carbon $fechaJornada, string $hora): Carbon
    {
        return Carbon::parse($fechaJornada->toDateString().' '.$hora);
    }
}
