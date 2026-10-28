<?php

namespace App\Services;

use App\Models\NominaSemanal;
use App\Models\PeriodoNomina;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionarPeriodoNominaService
{
    public function regenerar(PeriodoNomina $periodo, int $usuarioId): PeriodoNomina
    {
        if (! in_array($periodo->estatus, ['generada', 'revisada'], true)) {
            throw ValidationException::withMessages(['estatus' => 'Solo se pueden regenerar nóminas generadas o revisadas.']);
        }

        return DB::transaction(function () use ($periodo, $usuarioId): PeriodoNomina {
            NominaSemanal::query()
                ->where('periodo_nomina_id', $periodo->id)
                ->delete();

            $periodo->update([
                'estatus' => 'abierto',
                'revisada_por_id' => null,
                'revisada_en' => null,
                'cerrada_por_id' => null,
                'cerrada_en' => null,
            ]);

            return app(GenerarNominaSemanalService::class)->generar($periodo, $usuarioId);
        });
    }

    public function eliminar(PeriodoNomina $periodo): void
    {
        if ($periodo->estatus === 'cerrada') {
            throw ValidationException::withMessages(['estatus' => 'Una nómina cerrada no se puede eliminar.']);
        }

        $periodo->delete();
    }

    public function revisar(PeriodoNomina $periodo, int $usuarioId): PeriodoNomina
    {
        if ($periodo->estatus !== 'generada') {
            throw ValidationException::withMessages(['estatus' => 'Solo se pueden revisar nóminas generadas.']);
        }
        $periodo->update(['estatus' => 'revisada', 'revisada_por_id' => $usuarioId, 'revisada_en' => now()]);

        return $periodo->refresh();
    }

    public function cerrar(PeriodoNomina $periodo, int $usuarioId): PeriodoNomina
    {
        if ($periodo->estatus !== 'revisada') {
            throw ValidationException::withMessages(['estatus' => 'La nómina debe estar revisada antes de cerrarse.']);
        }
        $periodo->update(['estatus' => 'cerrada', 'cerrada_por_id' => $usuarioId, 'cerrada_en' => now()]);

        return $periodo->refresh();
    }
}
