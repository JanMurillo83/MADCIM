<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use SplFileObject;

class SatCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $this->importarCatalogoProductos($now);
        $this->importarCatalogoUnidades($now);

        DB::table('sat_tipo_comprobante')->upsert(
            [
                ['clave' => 'I', 'descripcion' => 'Ingreso', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'E', 'descripcion' => 'Egreso', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'T', 'descripcion' => 'Traslado', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'N', 'descripcion' => 'Nómina', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'P', 'descripcion' => 'Pago', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'updated_at']
        );

        DB::table('sat_moneda')->upsert(
            [
                ['clave' => 'MXN', 'descripcion' => 'Peso Mexicano', 'decimales' => 2, 'porcentaje_variacion' => null, 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'decimales', 'porcentaje_variacion', 'updated_at']
        );

        DB::table('sat_exportacion')->upsert(
            [
                ['clave' => '01', 'descripcion' => 'No aplica', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '02', 'descripcion' => 'Definitiva', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '03', 'descripcion' => 'Temporal', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'updated_at']
        );

        DB::table('sat_uso_cfdi')->upsert(
            [
                ['clave' => 'CP01', 'descripcion' => 'Pagos', 'aplica_fisica' => true, 'aplica_moral' => true, 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'aplica_fisica', 'aplica_moral', 'updated_at']
        );

        DB::table('sat_forma_pago')->upsert(
            [
                ['clave' => '01', 'descripcion' => 'Efectivo', 'bancarizado' => false, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '02', 'descripcion' => 'Cheque nominativo', 'bancarizado' => true, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '03', 'descripcion' => 'Transferencia electrónica de fondos', 'bancarizado' => true, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '04', 'descripcion' => 'Tarjeta de crédito', 'bancarizado' => true, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '28', 'descripcion' => 'Tarjeta de débito', 'bancarizado' => true, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '99', 'descripcion' => 'Por definir', 'bancarizado' => false, 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'bancarizado', 'updated_at']
        );

        DB::table('sat_metodo_pago')->upsert(
            [
                ['clave' => 'PUE', 'descripcion' => 'Pago en una sola exhibición', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'PPD', 'descripcion' => 'Pago en parcialidades o diferido', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'updated_at']
        );
    }

    private function importarCatalogoProductos(\Illuminate\Support\Carbon|\Carbon\Carbon $now): void
    {
        $path = database_path('seeders/SAT/CveSAT.csv');
        if (!is_file($path)) {
            throw new \RuntimeException("No se encontró el catálogo SAT de productos: {$path}");
        }

        $csv = new SplFileObject($path, 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
        $csv->setCsvControl(',', '"', '\\');
        $header = $csv->fgetcsv();
        $indices = $this->indicesColumnas($header ?: []);

        if (!isset($indices['clave'], $indices['descripcion'])) {
            throw new \RuntimeException('El CSV CveSAT.csv debe incluir las columnas CLAVE y DESCRIPCION.');
        }

        $this->importarFilas($csv, function (array $row) use ($indices, $now): ?array {
            $clave = trim((string) ($row[$indices['clave']] ?? ''));
            $descripcion = trim((string) ($row[$indices['descripcion']] ?? ''));

            if ($clave === '' || $descripcion === '') {
                return null;
            }

            return [
                'clave' => $clave,
                'descripcion' => $descripcion,
                'palabras_similares' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, 'sat_clave_prod_serv');
    }

    private function importarCatalogoUnidades(\Illuminate\Support\Carbon|\Carbon\Carbon $now): void
    {
        $path = database_path('seeders/SAT/Unidades.csv');
        if (!is_file($path)) {
            throw new \RuntimeException("No se encontró el catálogo SAT de unidades: {$path}");
        }

        $csv = new SplFileObject($path, 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
        $csv->setCsvControl(',', '"', '\\');
        $header = $csv->fgetcsv();
        $indices = $this->indicesColumnas($header ?: []);

        if (!isset($indices['clave'], $indices['nombre'], $indices['unidad'])) {
            throw new \RuntimeException('El CSV Unidades.csv debe incluir CLAVE, NOMBRE y UNIDAD.');
        }

        $this->importarFilas($csv, function (array $row) use ($indices, $now): ?array {
            $clave = trim((string) ($row[$indices['clave']] ?? ''));
            $nombre = trim((string) ($row[$indices['nombre']] ?? ''));
            $simbolo = trim((string) ($row[$indices['unidad']] ?? ''));

            if ($clave === '' || $nombre === '') {
                return null;
            }

            return [
                'clave' => $clave,
                'nombre' => $nombre,
                'descripcion' => $nombre,
                'simbolo' => $simbolo !== '' ? $simbolo : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, 'sat_clave_unidad');
    }

    /** @param array<int, string|null> $header
     *  @return array<string, int>
     */
    private function indicesColumnas(array $header): array
    {
        $indices = [];
        foreach ($header as $index => $nombre) {
            $nombre = strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $nombre)));
            if ($nombre !== '') {
                $indices[$nombre] = $index;
            }
        }

        return $indices;
    }

    /** @param callable(array<int, string|null>): ?array $mapear
     */
    private function importarFilas(SplFileObject $csv, callable $mapear, string $tabla): void
    {
        $buffer = [];
        while (!$csv->eof()) {
            $row = $csv->fgetcsv();
            if (!is_array($row) || count($row) < 2) {
                continue;
            }

            $registro = $mapear($row);
            if ($registro === null) {
                continue;
            }

            $buffer[] = $registro;
            if (count($buffer) === 500) {
                $this->guardarLote($tabla, $buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            $this->guardarLote($tabla, $buffer);
        }
    }

    /** @param array<int, array<string, mixed>> $lote
     */
    private function guardarLote(string $tabla, array $lote): void
    {
        DB::table($tabla)->upsert(
            $lote,
            ['clave'],
            array_values(array_diff(array_keys($lote[0]), ['clave', 'created_at'])),
        );
    }
}
