<?php

namespace App\Services;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoIncidencia;
use App\Models\Checada;
use App\Models\Empleado;
use App\Models\Incidencia;
use Illuminate\Support\Carbon;

class SincronizarIncidenciasAsistenciaService
{
    public function __construct(
        private readonly EvaluarAsistenciaService $evaluarAsistencia,
    ) {}

    public function sincronizar(Carbon|string $fechaInicio, Carbon|string $fechaFin, ?int $sucursalId = null): void
    {
        $inicio = $fechaInicio instanceof Carbon ? $fechaInicio->copy()->startOfDay() : Carbon::parse($fechaInicio)->startOfDay();
        $fin = $fechaFin instanceof Carbon ? $fechaFin->copy()->startOfDay() : Carbon::parse($fechaFin)->startOfDay();

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $hayChecadasEnElDia = Checada::query()
                ->withoutGlobalScope('sucursal')
                ->whereBetween('registrada_en', [$fecha->copy()->startOfDay(), $fecha->copy()->endOfDay()])
                ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
                ->exists();

            foreach ($this->evaluarAsistencia->evaluar($fecha, $sucursalId) as $fila) {
                $empleado = Empleado::query()->withoutGlobalScope('sucursal')->find($fila['empleado_id']);
                if (! $empleado) {
                    continue;
                }

                if ($hayChecadasEnElDia && $fila['estado'] === 'ausente') {
                    $this->crearSiNoExiste($empleado, $fecha, TipoIncidencia::FaltaJustificada, [
                        'dias' => 1,
                        'motivo' => 'Falta detectada automáticamente; requiere autorización.',
                    ]);
                }

                if ($fila['minutos_retardo_entrada'] > 0) {
                    $this->crearSiNoExiste($empleado, $fecha, TipoIncidencia::RetardoAutorizado, [
                        'minutos' => $fila['minutos_retardo_entrada'],
                        'motivo' => 'Retardo de entrada detectado automáticamente; requiere autorización.',
                    ]);
                }

                if ($fila['minutos_comida_excedidos'] > 0) {
                    $this->crearSiNoExiste($empleado, $fecha, TipoIncidencia::ComidaExcedida, [
                        'minutos' => $fila['minutos_comida_excedidos'],
                        'motivo' => 'Exceso de comida detectado automáticamente; requiere autorización.',
                    ]);
                }
            }
        }
    }

    private function crearSiNoExiste(Empleado $empleado, Carbon $fecha, TipoIncidencia $tipo, array $datos): void
    {
        $existe = Incidencia::query()
            ->withoutGlobalScope('sucursal')
            ->where('empleado_id', $empleado->id)
            ->whereDate('fecha_inicio', $fecha)
            ->where('tipo', $tipo)
            ->exists();

        if ($existe) {
            return;
        }

        Incidencia::create([
            'empleado_id' => $empleado->id,
            'sucursal_id' => $empleado->sucursal_id,
            'tipo' => $tipo,
            'estatus' => EstadoIncidencia::Pendiente,
            'fecha_inicio' => $fecha->toDateString(),
            'fecha_fin' => $fecha->toDateString(),
            'afecta_nomina' => false,
            'solicitada_por_id' => auth()->id(),
            ...$datos,
        ]);
    }
}
