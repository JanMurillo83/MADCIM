<?php

namespace App\Services;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoIncidencia;
use App\Models\ConceptoNomina;
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
    ) {}

    public function generar(PeriodoNomina $periodo, ?int $usuarioId = null): PeriodoNomina
    {
        if ($periodo->estatus !== 'abierto') {
            throw ValidationException::withMessages(['estatus' => 'El periodo ya fue generado y no puede regenerarse desde este estado.']);
        }

        return DB::transaction(function () use ($periodo, $usuarioId): PeriodoNomina {
            $inicio = $periodo->fecha_inicio;
            $fin = $periodo->fecha_fin;
            app(SincronizarIncidenciasAsistenciaService::class)->sincronizar($inicio, $fin);
            if (Incidencia::query()
                ->where('estatus', EstadoIncidencia::Pendiente)
                ->whereDate('fecha_inicio', '<=', $fin)
                ->where(function ($query) use ($inicio): void {
                    $query->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio);
                })
                ->exists()) {
                throw ValidationException::withMessages([
                    'estatus' => 'La nómina no puede generarse mientras existan incidencias pendientes de autorización.',
                ]);
            }
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
                        ->where(function ($query): void {
                            $query
                                ->where('tipo', TipoIncidencia::PermisoSinGoce)
                                ->orWhere(function ($query): void {
                                    $query
                                        ->where('tipo', TipoIncidencia::FaltaJustificada)
                                        ->where('afecta_nomina', true);
                                });
                        })
                        ->whereBetween('fecha_inicio', [$inicio, $fin])
                        ->sum('dias');
                    $descuentoImporte = (float) $descuentos * (float) ($salario?->sueldo_diario ?? $empleado->sueldo_diario ?? 0);
                    $sueldoDiario = (float) ($salario?->sueldo_diario ?? $empleado->sueldo_diario ?? 0);
                    $asistenciaEmpleado = $asistencia->get($empleado->id, []);
                    $faltas = (int) ($asistenciaEmpleado['faltas'] ?? 0);
                    $diasProgramados = (int) ($asistenciaEmpleado['dias_programados'] ?? 0);
                    $diasTrabajados = (int) ($asistenciaEmpleado['dias_laborados'] ?? 0);
                    $minutosRetardo = (int) ($asistenciaEmpleado['minutos_retardo'] ?? 0);
                    $descuentoFaltas = $faltas * $sueldoDiario;
                    $descuentoRetardos = ($minutosRetardo / 60) * ($sueldoDiario / 8);
                    $conceptos = ConceptoNomina::query()
                        ->where('periodo_nomina_id', $periodo->id)
                        ->where('empleado_id', $empleado->id)
                        ->get();
                    $bonos = (float) $conceptos->where('tipo', 'bono')->sum('importe');
                    $aguinaldo = (float) $conceptos->where('tipo', 'aguinaldo')->sum('importe');
                    $apoyos = (float) $conceptos->where('tipo', 'apoyo')->sum('importe');
                    $importeDiaFestivo = (float) $conceptos->where('tipo', 'dia_festivo')->sum('importe');
                    $importeDescansoLaborado = (float) $conceptos->where('tipo', 'dia_descanso')->sum('importe');
                    $descuentosManuales = (float) $conceptos->where('tipo', 'descuento')->sum('importe');
                    $anticipos = (float) $conceptos->where('tipo', 'anticipo')->sum('importe');
                    $prestamos = (float) $conceptos->where('tipo', 'prestamo')->sum('importe');
                    $otrosDescuentos = $descuentoImporte + $descuentosManuales;
                    $descuentosTotal = $otrosDescuentos + $anticipos + $prestamos + $descuentoFaltas + $descuentoRetardos;
                    $total = max(0, $sueldoBase + $pagoExtra + $bonos + $aguinaldo + $apoyos + $importeDiaFestivo + $importeDescansoLaborado - $descuentosTotal);

                    NominaSemanal::updateOrCreate(
                        ['periodo_nomina_id' => $periodo->id, 'empleado_id' => $empleado->id],
                        [
                            'sueldo_base' => $sueldoBase,
                            'dias_programados' => $diasProgramados,
                            'dias_trabajados' => $diasTrabajados,
                            'dias_descanso_laborados' => (int) $conceptos->where('tipo', 'dia_descanso')->sum('cantidad'),
                            'dias_festivos' => (int) $conceptos->where('tipo', 'dia_festivo')->sum('cantidad'),
                            'importe_descanso_laborado' => $importeDescansoLaborado,
                            'importe_dia_festivo' => $importeDiaFestivo,
                            'faltas' => $faltas,
                            'minutos_retardo' => $minutosRetardo,
                            'descuento_faltas' => $descuentoFaltas,
                            'descuento_retardos' => $descuentoRetardos,
                            'otros_descuentos' => $otrosDescuentos,
                            'horas_extra' => $pagoExtra,
                            'bonos' => $bonos,
                            'aguinaldo' => $aguinaldo,
                            'apoyos' => $apoyos,
                            'anticipos' => $anticipos,
                            'prestamos' => $prestamos,
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
