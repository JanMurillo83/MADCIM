<?php

namespace App\Services;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoIncidencia;
use App\Models\Empleado;
use App\Models\HistorialSalarial;
use App\Models\Incidencia;
use App\Models\NominaSemanal;
use App\Models\PeriodoNomina;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerarNominaSemanalService
{
    public function __construct(
        private readonly GenerarReporteAsistenciaService $reporteAsistencia,
    ) {
    }

    public function generar(PeriodoNomina $periodo, ?int $usuarioId = null): PeriodoNomina
    {
        if ($periodo->estatus !== 'abierto') {
            throw ValidationException::withMessages(['estatus' => 'El periodo ya fue generado o cerrado.']);
        }

        return DB::transaction(function () use ($periodo, $usuarioId): PeriodoNomina {
            $inicio = $periodo->fecha_inicio;
            $fin = $periodo->fecha_fin;
            $asistencia = $this->reporteAsistencia->generar($inicio, $fin)->keyBy('empleado_id');

            Empleado::query()
                ->where('estatus', 'activo')
                ->each(function (Empleado $empleado) use ($periodo, $inicio, $fin, $asistencia): void {
                    $salario = HistorialSalarial::query()
                        ->where('empleado_id', $empleado->id)
                        ->whereDate('fecha_inicio', '<=', $fin)
                        ->where(function ($query) use ($inicio): void {
                            $query->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio);
                        })
                        ->orderByDesc('fecha_inicio')
                        ->first();

                    $sueldoBase = (float) ($salario?->sueldo_semanal ?? $empleado->sueldo_semanal ?? ((float) $empleado->sueldo_diario * 7));
                    $horasExtra = Incidencia::query()
                        ->where('empleado_id', $empleado->id)
                        ->where('tipo', TipoIncidencia::HoraExtra)
                        ->where('estatus', EstadoIncidencia::Aprobada)
                        ->whereBetween('fecha_inicio', [$inicio, $fin])
                        ->sum('minutos');
                    $valorHora = ((float) ($salario?->sueldo_diario ?? $empleado->sueldo_diario ?? 0) / 8);
                    $pagoExtra = ((float) $horasExtra / 60) * $valorHora * 1.5;

                    $descuentos = Incidencia::query()
                        ->where('empleado_id', $empleado->id)
                        ->where('estatus', EstadoIncidencia::Aprobada)
                        ->whereIn('tipo', [TipoIncidencia::PermisoSinGoce, TipoIncidencia::FaltaJustificada])
                        ->whereBetween('fecha_inicio', [$inicio, $fin])
                        ->sum('dias');
                    $descuentoImporte = (float) $descuentos * (float) ($salario?->sueldo_diario ?? $empleado->sueldo_diario ?? 0);
                    $sueldoDiario = (float) ($salario?->sueldo_diario ?? $empleado->sueldo_diario ?? 0);
                    $asistenciaEmpleado = $asistencia->get($empleado->id, []);
                    $faltas = (int) ($asistenciaEmpleado['faltas'] ?? 0);
                    $minutosRetardo = (int) ($asistenciaEmpleado['minutos_retardo'] ?? 0);
                    $descuentoFaltas = $faltas * $sueldoDiario;
                    $descuentoRetardos = ($minutosRetardo / 60) * ($sueldoDiario / 8);
                    $descuentosTotal = $descuentoImporte + $descuentoFaltas + $descuentoRetardos;
                    $total = max(0, $sueldoBase + $pagoExtra - $descuentosTotal);

                    NominaSemanal::updateOrCreate(
                        ['periodo_nomina_id' => $periodo->id, 'empleado_id' => $empleado->id],
                        [
                            'sueldo_base' => $sueldoBase,
                            'faltas' => $faltas,
                            'minutos_retardo' => $minutosRetardo,
                            'descuento_faltas' => $descuentoFaltas,
                            'descuento_retardos' => $descuentoRetardos,
                            'otros_descuentos' => $descuentoImporte,
                            'horas_extra' => $pagoExtra,
                            'bonos' => 0,
                            'descuentos' => $descuentosTotal,
                            'total' => $total,
                        ],
                    );
                });

            $periodo->update([
                'estatus' => 'generada',
                'generada_por_id' => $usuarioId,
                'generada_en' => now(),
            ]);

            return $periodo->refresh();
        });
    }
}
