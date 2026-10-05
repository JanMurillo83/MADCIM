<?php

namespace App\Console\Commands;

use App\Services\ProductosImportService;
use App\Services\ResetOperationalDataService;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;
use RuntimeException;

class ReiniciarSistemaCompleto extends Command
{
    protected $signature = 'sistema:reiniciar-completo
                            {catalogo? : Ruta del Excel; por defecto database/invdata/CATALOGO PRODUCTOS RENTAS.xlsx}
                            {--force : Ejecuta el reinicio sin solicitar confirmacion}';

    protected $description = 'Reinicia las operaciones y reemplaza el catalogo de productos';

    public function handle(
        ResetOperationalDataService $resetService,
        ProductosImportService $productosImportService,
    ): int {
        $path = (string) ($this->argument('catalogo') ?: database_path('invdata/CATALOGO PRODUCTOS RENTAS.xlsx'));

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("No se puede leer el archivo de catalogo: {$path}");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Se eliminaran datos operativos, saldos y el catalogo actual; tambien se reiniciaran folios y cajas. ¿Continuar?'
        )) {
            $this->info('Reinicio cancelado.');

            return self::SUCCESS;
        }

        try {
            $result = $resetService->resetWithProductCatalog($path, $productosImportService);
        } catch (RuntimeException|SpreadsheetReaderException $exception) {
            $this->error('Reinicio no realizado: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Reinicio completo. Tablas limpiadas: {$result['tables']}. Productos cargados: {$result['products']}.");
        $this->warn('La configuracion de precios por M2 se conservo; verifique sus seis importes antes de iniciar capturas.');
        $this->warn('DEPOGARANTIA se conserva desde el catalogo legado; verifique su precio de venta antes de cotizar.');

        return self::SUCCESS;
    }
}
