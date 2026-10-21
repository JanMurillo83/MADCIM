<?php

namespace App\Services;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoIncidencia;
use App\Models\Incidencia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GenerarReporteAsistenciaService
{
    public function __construct(
        private readonly EvaluarAsistenciaService $evaluarAsistencia,
    ) {
    }

    public function generar(Carbon|string $fechaInicio, Carbon|string $fechaFin, ?int $sucursalId = null, ?int $empleadoId = null): Collection
    {
        $inicio = $fechaInicio instanceof Carbon ? $fechaInicio->copy()->startOfDay() : Carbon::parse($fechaInicio)->startOfDay();
        $fin = $fechaFin instanceof Carbon ? $fechaFin->copy()->startOfDay() : Carbon::parse($fechaFin)->startOfDay();
        $acumulado = collect();

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $filas = $this->evaluarAsistencia->evaluar($fecha, $sucursalId)
                ->when($empleadoId, fn (Collection $rows): Collection => $rows->where('empleado_id', $empleadoId));

            foreach ($filas as $fila) {
                $actual = $acumulado->get($fila['empleado_id'], [
                    'empleado_id' => $fila['empleado_id'],
                    'numero_empleado' => $fila['numero_empleado'],
                    'nombre' => $fila['nombre'],
                    'sucursal' => $fila['sucursal'],
                    'dias_programados' => 0,
                    'dias_laborados' => 0,
                    'puntuales' => 0,
                    'retardos' => 0,
                    'faltas' => 0,
                    'sin_salida' => 0,
                    'minutos_retardo' => 0,
                    'minutos_comida_excedidos' => 0,
                    'minutos_salida_anticipada' => 0,
                    'minutos_efectivos' => 0,
                    'horas_extra_autorizadas' => 0,
                ]);

                if ($fila['estado'] !== 'descanso' && $fila['estado'] !== 'sin_horario') {
                    $actual['dias_programados']++;
                }

                if ($fila['entrada']) {
                    $actual['dias_laborados']++;
                }

                if (in_array($fila['estado'], ['puntual', 'tolerancia'], true)) {
                    $actual['puntuales']++;
                }

                if ($fila['estado'] === 'retardo') {
                    $actual['retardos']++;
                }

                if ($fila['estado'] === 'ausente') {
                    $actual['faltas']++;
                }

                if ($fila['estado'] === 'sin_salida') {
                    $actual['sin_salida']++;
                }

                $actual['minutos_retardo'] += $fila['minutos_retardo'];
                $actual['minutos_comida_excedidos'] += $fila['minutos_comida_excedidos'];
                $actual['minutos_salida_anticipada'] += $fila['minutos_salida_anticipada'];
                $actual['minutos_efectivos'] += $fila['minutos_efectivos'];
                $acumulado->put($fila['empleado_id'], $actual);
            }
        }

        $incidencias = Incidencia::query()
            ->where('estatus', EstadoIncidencia::Aprobada)
            ->whereDate('fecha_inicio', '<=', $fin)
            ->where(function ($query) use ($inicio): void {
                $query->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio);
            })
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->when($empleadoId, fn ($query) => $query->where('empleado_id', $empleadoId))
            ->get();

        foreach ($incidencias as $incidencia) {
            if (!$acumulado->has($incidencia->empleado_id)) {
                continue;
            }

            $fila = $acumulado->get($incidencia->empleado_id);
            if ($incidencia->tipo === TipoIncidencia::HoraExtra) {
                $fila['horas_extra_autorizadas'] += (int) ($incidencia->minutos ?? 0);
            }
            if ($incidencia->tipo === TipoIncidencia::FaltaJustificada) {
                $fila['faltas'] = max(0, $fila['faltas'] - (int) ($incidencia->dias ?? 1));
            }
            $acumulado->put($incidencia->empleado_id, $fila);
        }

        return $acumulado->values();
    }
}
