<?php

namespace App\Services;

use App\Models\Checada;
use App\Models\Empleado;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EvaluarAsistenciaService
{
    public function evaluar(Carbon|string $fecha, ?int $sucursalId = null): Collection
    {
        $fecha = $fecha instanceof Carbon ? $fecha->copy()->startOfDay() : Carbon::parse($fecha)->startOfDay();

        return Empleado::query()
            ->with(['horario', 'sucursal'])
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->where('estatus', 'activo')
            ->orderBy('apellido_paterno')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Empleado $empleado): array => $this->evaluarEmpleado($empleado, $fecha));
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluarEmpleado(Empleado $empleado, Carbon $fecha): array
    {
        $horario = $empleado->horario;
        $checadas = Checada::query()
            ->where('empleado_id', $empleado->id)
            ->whereDate('registrada_en', $fecha)
            ->orderBy('registrada_en')
            ->get()
            ->keyBy(fn (Checada $checada): string => $checada->tipo->value);

        $base = [
            'empleado_id' => $empleado->id,
            'numero_empleado' => $empleado->numero_empleado,
            'nombre' => $empleado->nombre_completo,
            'sucursal' => $empleado->sucursal?->nombre ?? 'Sin sucursal',
            'entrada' => $checadas->get('entrada')?->registrada_en,
            'salida_comida' => $checadas->get('salida_comida')?->registrada_en,
            'regreso_comida' => $checadas->get('regreso_comida')?->registrada_en,
            'salida' => $checadas->get('salida')?->registrada_en,
            'minutos_retardo' => 0,
            'minutos_comida_excedidos' => 0,
            'minutos_salida_anticipada' => 0,
            'minutos_efectivos' => 0,
            'estado' => 'sin_horario',
            'estado_label' => 'Sin horario',
        ];

        if (!$horario) {
            return $base;
        }

        if ($fecha->dayOfWeek === (int) $horario->dia_descanso) {
            return [
                ...$base,
                'estado' => 'descanso',
                'estado_label' => 'Descanso',
            ];
        }

        $entrada = $base['entrada'];

        if (!$entrada) {
            return [
                ...$base,
                'estado' => 'ausente',
                'estado_label' => 'Ausente',
            ];
        }

        $horaEntrada = $this->momento($fecha, $horario->hora_entrada);
        $minutosDesdeEntrada = (int) max(0, $horaEntrada->diffInMinutes($entrada, false));
        $tolerancia = (int) $horario->tolerancia_entrada;
        $estado = 'puntual';
        $estadoLabel = 'Puntual';

        if ($minutosDesdeEntrada > 0 && $minutosDesdeEntrada <= $tolerancia) {
            $estado = 'tolerancia';
            $estadoLabel = 'Dentro de tolerancia';
        } elseif ($minutosDesdeEntrada > $tolerancia) {
            $estado = 'retardo';
            $estadoLabel = 'Retardo';
        }

        $salidaComida = $base['salida_comida'];
        $regresoComida = $base['regreso_comida'];
        $minutosComidaExcedidos = 0;

        if ($salidaComida && $regresoComida) {
            $duracionComida = (int) $salidaComida->diffInMinutes($regresoComida);
            $limiteComida = (int) $horario->duracion_comida + (int) $horario->tolerancia_regreso_comida;
            $minutosComidaExcedidos = (int) max(0, $duracionComida - $limiteComida);
        }

        $salida = $base['salida'];
        $minutosSalidaAnticipada = 0;
        if ($salida) {
            $horaSalida = $this->momento($fecha, $horario->hora_salida);
            $minutosSalidaAnticipada = (int) max(0, $salida->diffInMinutes($horaSalida, false));
        }

        $minutosEfectivos = 0;
        if ($salida) {
            $minutosEfectivos = (int) $entrada->diffInMinutes($salida);
            if ($salidaComida && $regresoComida) {
                $minutosEfectivos -= $salidaComida->diffInMinutes($regresoComida);
            }
            $minutosEfectivos = (int) max(0, $minutosEfectivos);
        }

        return [
            ...$base,
            'minutos_retardo' => $minutosDesdeEntrada,
            'minutos_comida_excedidos' => $minutosComidaExcedidos,
            'minutos_salida_anticipada' => $minutosSalidaAnticipada,
            'minutos_efectivos' => $minutosEfectivos,
            'estado' => $salida ? $estado : 'sin_salida',
            'estado_label' => $salida ? $estadoLabel : 'Sin salida',
        ];
    }

    private function momento(Carbon $fecha, string $hora): Carbon
    {
        return Carbon::parse($fecha->toDateString().' '.$hora);
    }
}
