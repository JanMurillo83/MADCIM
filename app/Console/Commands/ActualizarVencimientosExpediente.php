<?php

namespace App\Console\Commands;

use App\Services\ActualizarVencimientosExpedienteService;
use Illuminate\Console\Command;

class ActualizarVencimientosExpediente extends Command
{
    protected $signature = 'rh:actualizar-vencimientos';

    protected $description = 'Actualiza el estatus de contratos y documentos laborales vencidos';

    public function handle(ActualizarVencimientosExpedienteService $service): int
    {
        $resultado = $service->actualizar();

        $this->info("Contratos actualizados: {$resultado['contratos']}.");
        $this->info("Documentos actualizados: {$resultado['documentos']}.");

        return self::SUCCESS;
    }
}
