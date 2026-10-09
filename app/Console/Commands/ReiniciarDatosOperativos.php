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
        $canPrompt = $this->input->isInteractive()
            && ! $this->option('force')
            && ! $this->option('no-interaction')
            && defined('STDIN')
            && stream_isatty(STDIN);

        if ($canPrompt && ! $this->confirm(
            'Esta accion eliminara clientes, proveedores y todos los datos operativos. ¿Desea continuar?',
            true,
        )) {
            $this->info('Reinicio cancelado.');

            return self::SUCCESS;
        }

        if (! $canPrompt && ! $this->option('force')) {
            $this->info('Confirmacion automatica: se acepto el reinicio por no haber una terminal interactiva.');
        }

        $tablesReset = $service->resetFromCommand();

        $this->info("Reinicio completado. Tablas limpiadas: {$tablesReset}. Usuarios, cajas, series y catalogos maestros conservados.");

        return self::SUCCESS;
    }
}
