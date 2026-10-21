<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\DocumentoEmpleado;
use Illuminate\Support\Carbon;

class ActualizarVencimientosExpedienteService
{
    public function actualizar(?Carbon $hoy = null): array
    {
        $hoy ??= today();
        $contratos = 0;
        $documentos = 0;

        Contrato::query()
            ->whereNotIn('estatus', ['cancelado'])
            ->whereNotNull('fecha_fin')
            ->eachById(function (Contrato $contrato) use ($hoy, &$contratos): void {
                $estatus = $contrato->fecha_fin->isPast() && ! $contrato->fecha_fin->isToday()
                    ? 'vencido'
                    : 'vigente';

                if ($contrato->estatus !== $estatus) {
                    $contrato->updateQuietly(['estatus' => $estatus]);
                    $contratos++;
                }
            });

        DocumentoEmpleado::query()
            ->whereNotIn('estatus', ['cancelado'])
            ->whereNotNull('fecha_vencimiento')
            ->eachById(function (DocumentoEmpleado $documento) use ($hoy, &$documentos): void {
                $estatus = $documento->fecha_vencimiento->isPast() && ! $documento->fecha_vencimiento->isToday()
                    ? 'vencido'
                    : 'vigente';

                if ($documento->estatus !== $estatus) {
                    $documento->updateQuietly(['estatus' => $estatus]);
                    $documentos++;
                }
            });

        return compact('contratos', 'documentos');
    }
}
