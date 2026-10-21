<?php
namespace App\Services;
use App\Models\PeriodoNomina;
use Illuminate\Validation\ValidationException;
class GestionarPeriodoNominaService
{
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
