<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SplFileObject;

class ImportarCatalogosSat extends Command
{
    private bool $importFailed = false;

    protected $signature = 'sat:importar-catalogos {productos_csv : CSV oficial de claves de producto o servicio} {unidades_csv : CSV oficial de claves de unidad}';

    protected $description = 'Carga los catálogos SAT de productos/servicios y unidades desde archivos CSV.';

    public function handle(): int
    {
        foreach (['productos_csv', 'unidades_csv'] as $argument) {
            $path = (string) $this->argument($argument);
            if (!is_file($path) || !is_readable($path)) {
                $this->error("No se puede leer el archivo: {$path}");
                return self::FAILURE;
            }
        }

        $productos = $this->importarCsv(
            (string) $this->argument('productos_csv'),
            'sat_clave_prod_serv',
            'prod_serv',
        );
        $unidades = $this->importarCsv(
            (string) $this->argument('unidades_csv'),
            'sat_clave_unidad',
            'unidad',
        );

        $this->info("Claves de producto/servicio cargadas: {$productos}");
        $this->info("Claves de unidad cargadas: {$unidades}");

        return $this->importFailed ? self::FAILURE : self::SUCCESS;
    }

    private function importarCsv(string $path, string $table, string $tipo): int
    {
        if (!is_file($path) || !is_readable($path)) {
            $this->error("No se puede leer el archivo: {$path}");
            $this->importFailed = true;
            return 0;
        }

        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl($this->detectarDelimitador($path), '"', '\\');
        $header = $file->fgetcsv();

        if (!is_array($header) || count($header) < 2) {
            $this->error("El archivo CSV no tiene encabezados válidos: {$path}");
            $this->importFailed = true;
            return 0;
        }

        $header = array_map(fn ($value): string => $this->normalizarEncabezado((string) $value), $header);
        $indices = $this->resolverIndices($header, $tipo);

        if ($indices['clave'] === null || $indices['descripcion'] === null) {
            $this->error("No se reconocieron las columnas de clave y descripción en: {$path}");
            $this->importFailed = true;
            return 0;
        }

        $buffer = [];
        $total = 0;
        $now = now();

        while (!$file->eof()) {
            $row = $file->fgetcsv();
            if (!is_array($row) || count($row) < count($header)) {
                continue;
            }

            $clave = trim((string) ($row[$indices['clave']] ?? ''));
            $descripcion = trim((string) ($row[$indices['descripcion']] ?? ''));
            if ($clave === '' || $descripcion === '') {
                continue;
            }

            $values = [
                'clave' => $clave,
                'descripcion' => $descripcion,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($tipo === 'prod_serv') {
                $values['palabras_similares'] = $indices['similares'] === null
                    ? null
                    : (trim((string) ($row[$indices['similares']] ?? '')) ?: null);
            } else {
                $values['nombre'] = $indices['nombre'] === null
                    ? $descripcion
                    : (trim((string) ($row[$indices['nombre']] ?? '')) ?: $descripcion);
                $values['simbolo'] = $indices['simbolo'] === null
                    ? null
                    : (trim((string) ($row[$indices['simbolo']] ?? '')) ?: null);
            }

            $buffer[] = $values;
            if (count($buffer) >= 500) {
                DB::table($table)->upsert($buffer, ['clave'], array_values(array_diff(array_keys($values), ['clave', 'created_at'])));
                $total += count($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table($table)->upsert($buffer, ['clave'], array_values(array_diff(array_keys($buffer[0]), ['clave', 'created_at'])));
            $total += count($buffer);
        }

        return $total;
    }

    private function detectarDelimitador(string $path): string
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ',';
        }
        $line = (string) fgets($handle);
        fclose($handle);
        $counts = [
            ';' => substr_count($line, ';'),
            ',' => substr_count($line, ','),
            "\t" => substr_count($line, "\t"),
            '|' => substr_count($line, '|'),
        ];

        return array_search(max($counts), $counts, true) ?: ',';
    }

    /** @param array<int, string> $header
     *  @return array{clave: ?int, descripcion: ?int, similares: ?int, nombre: ?int, simbolo: ?int}
     */
    private function resolverIndices(array $header, string $tipo): array
    {
        $find = static function (array $needles) use ($header): ?int {
            foreach ($header as $index => $value) {
                foreach ($needles as $needle) {
                    if (str_contains($value, $needle)) {
                        return $index;
                    }
                }
            }
            return null;
        };

        return [
            'clave' => $find($tipo === 'prod_serv' ? ['claveprodserv', 'clave'] : ['claveunidad', 'clave']),
            'descripcion' => $find(['descripcion']),
            'similares' => $tipo === 'prod_serv' ? $find(['palabrassimilares', 'similares']) : null,
            'nombre' => $tipo === 'unidad' ? $find(['nombre']) : null,
            'simbolo' => $tipo === 'unidad' ? $find(['simbolo']) : null,
        ];
    }

    private function normalizarEncabezado(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        return Str::lower(preg_replace('/[^a-z0-9]/', '', Str::ascii(trim($value))) ?? '');
    }
}
