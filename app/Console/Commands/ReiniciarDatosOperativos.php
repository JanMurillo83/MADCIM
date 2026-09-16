<?php

namespace App\Console\Commands;

use App\Services\ResetOperationalDataService;
use Illuminate\Console\Command;

class ReiniciarDatosOperativos extends Command
{
    protected $signature = 'sistema:reiniciar-datos
                            {--force : Ejecuta el reinicio sin solicitar confirmacion}';

    protected $description = 'Elimina los datos operativos y conserva los catalogos maestros';

    public function handle(ResetOperationalDataService $service): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'Esta accion eliminara todos los datos operativos. ¿Desea continuar?'
        )) {
            $this->info('Reinicio cancelado.');

            return self::SUCCESS;
        }

        $tablesReset = $service->resetFromCommand();

        $this->info("Reinicio completado. Tablas limpiadas: {$tablesReset}.");

        return self::SUCCESS;
    }
}
