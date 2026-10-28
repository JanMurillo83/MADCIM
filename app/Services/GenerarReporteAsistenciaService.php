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
    ) {}

    public function generar(Carbon|string $fechaInicio, Carbon|string $fechaFin, ?int $sucursalId = null, ?int $empleadoId = null): Collection
    {
        $inicio = $fechaInicio instanceof Carbon ? $fechaInicio->copy()->startOfDay() : Carbon::parse($fechaInicio)->startOfDay();
        $fin = $fechaFin instanceof Carbon ? $fechaFin->copy()->startOfDay() : Carbon::parse($fechaFin)->startOfDay();
        $acumulado = collect();
        app(SincronizarIncidenciasAsistenciaService::class)->sincronizar($inicio, $fin, $sucursalId);
        $incidencias = Incidencia::query()
            ->whereIn('estatus', [EstadoIncidencia::Aprobada, EstadoIncidencia::Rechazada])
            ->whereDate('fecha_inicio', '<=', $fin)
            ->where(function ($query) use ($inicio): void {
                $query->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio);
            })
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->when($empleadoId, fn ($query) => $query->where('empleado_id', $empleadoId))
            ->get();

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $filas = $this->evaluarAsistencia->evaluar($fecha, $sucursalId)
                ->when($empleadoId, fn (Collection $rows): Collection => $rows->where('empleado_id', $empleadoId));

            foreach ($filas as $fila) {
                $incidenciasDelDia = $incidencias
                    ->where('empleado_id', $fila['empleado_id'])
                    ->filter(fn (Incidencia $incidencia): bool => $incidencia->fecha_inicio->startOfDay()->lte($fecha)
                        && ($incidencia->fecha_fin === null || $incidencia->fecha_fin->startOfDay()->gte($fecha)));

                foreach ($incidenciasDelDia as $incidencia) {
                    $fila = $this->aplicarIncidencia($fila, $incidencia);
                }

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

                if ($fila['es_retardo']) {
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
                $actual['horas_extra_autorizadas'] += $fila['horas_extra_autorizadas'] ?? 0;
                $acumulado->put($fila['empleado_id'], $actual);
            }
        }

        return $acumulado->values();
    }

    /** @param array<string, mixed> $fila */
    private function aplicarIncidencia(array $fila, Incidencia $incidencia): array
    {
        $fila['faltas'] ??= 0;
        $fila['horas_extra_autorizadas'] ??= 0;
        $dias = max(1, (int) ($incidencia->dias ?? 1));

        if ($incidencia->estatus === EstadoIncidencia::Aprobada && $incidencia->tipo === TipoIncidencia::HoraExtra) {
            $fila['horas_extra_autorizadas'] += (int) ($incidencia->minutos ?? 0);
        }

        if ($incidencia->estatus === EstadoIncidencia::Aprobada && in_array($incidencia->tipo, [
            TipoIncidencia::FaltaJustificada,
            TipoIncidencia::PermisoConGoce,
            TipoIncidencia::PermisoSinGoce,
        ], true)) {
            $fila['faltas'] = max(0, $fila['faltas'] - $dias);
        }

        if ($incidencia->tipo === TipoIncidencia::RetardoAutorizado && $incidencia->estatus === EstadoIncidencia::Rechazada) {
            $fila['entrada'] = null;
            $fila['estado'] = 'ausente';
            $fila['estado_label'] = 'Falta por retardo no autorizado';
            $fila['minutos_retardo'] = 0;
            $fila['es_retardo'] = false;
            $fila['faltas']++;
        } elseif ($incidencia->tipo === TipoIncidencia::RetardoAutorizado && ! $incidencia->afecta_nomina) {
            $fila['minutos_retardo'] = max(0, $fila['minutos_retardo'] - $fila['minutos_retardo_entrada']);
            $fila['es_retardo'] = $fila['minutos_retardo'] > 0;
        }

        if ($incidencia->tipo === TipoIncidencia::ComidaExcedida
            && $incidencia->estatus === EstadoIncidencia::Aprobada
            && ! $incidencia->afecta_nomina) {
            $minutos = (int) ($incidencia->minutos ?? 0);
            $fila['minutos_retardo'] = max(0, $fila['minutos_retardo'] - $minutos);
            $fila['minutos_comida_excedidos'] = max(0, $fila['minutos_comida_excedidos'] - $minutos);
            $fila['es_retardo'] = $fila['minutos_retardo'] > 0;
        }

        return $fila;
    }
}
