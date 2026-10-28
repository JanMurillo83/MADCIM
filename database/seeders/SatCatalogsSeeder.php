<?php

namespace Database\Seeders;

use Carbon\Carbon;
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
            array_map(static fn (array $uso): array => [
                'clave' => $uso[0],
                'descripcion' => $uso[1],
                'aplica_fisica' => $uso[2],
                'aplica_moral' => $uso[3],
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                ['G01', 'Adquisición de mercancías', true, true],
                ['G02', 'Devoluciones, descuentos o bonificaciones', true, true],
                ['G03', 'Gastos en general', true, true],
                ['I01', 'Construcciones', true, true],
                ['I02', 'Mobiliario y equipo de oficina por inversiones', true, true],
                ['I03', 'Equipo de transporte', true, true],
                ['I04', 'Equipo de cómputo y accesorios', true, true],
                ['I05', 'Dados, troqueles, moldes, matrices y herramental', true, true],
                ['I06', 'Comunicaciones telefónicas', true, true],
                ['I07', 'Comunicaciones satelitales', true, true],
                ['I08', 'Otra maquinaria y equipo', true, true],
                ['D01', 'Honorarios médicos, dentales y gastos hospitalarios', true, false],
                ['D02', 'Gastos médicos por incapacidad o discapacidad', true, false],
                ['D03', 'Gastos funerales', true, false],
                ['D04', 'Donativos', true, false],
                ['D05', 'Intereses reales efectivamente pagados por créditos hipotecarios', true, false],
                ['D06', 'Aportaciones voluntarias al SAR', true, false],
                ['D07', 'Primas por seguros de gastos médicos', true, false],
                ['D08', 'Gastos de transportación escolar obligatoria', true, false],
                ['D09', 'Depósitos en cuentas para el ahorro', true, false],
                ['D10', 'Pagos por servicios educativos', true, false],
                ['S01', 'Sin efectos fiscales', true, true],
                ['CP01', 'Pagos', true, true],
                ['CN01', 'Nómina', true, false],
            ]),
            ['clave'],
            ['descripcion', 'aplica_fisica', 'aplica_moral', 'updated_at']
        );

        DB::table('sat_forma_pago')->upsert(
            array_map(static fn (array $forma): array => [
                'clave' => $forma[0],
                'descripcion' => $forma[1],
                'bancarizado' => $forma[2],
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                ['01', 'Efectivo', false],
                ['02', 'Cheque nominativo', true],
                ['03', 'Transferencia electrónica de fondos', true],
                ['04', 'Tarjeta de crédito', true],
                ['05', 'Monedero electrónico', true],
                ['06', 'Dinero electrónico', true],
                ['08', 'Vales de despensa', false],
                ['12', 'Dación en pago', false],
                ['13', 'Pago por subrogación', false],
                ['14', 'Pago por consignación', false],
                ['15', 'Condonación', false],
                ['17', 'Compensación', false],
                ['23', 'Novación', false],
                ['24', 'Confusión', false],
                ['25', 'Remisión de deuda', false],
                ['26', 'Prescripción o caducidad', false],
                ['27', 'A satisfacción del acreedor', false],
                ['28', 'Tarjeta de débito', true],
                ['29', 'Tarjeta de servicios', true],
                ['30', 'Aplicación de anticipos', false],
                ['31', 'Intermediario pagos', true],
                ['99', 'Por definir', false],
            ]),
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

        DB::table('sat_motivo_cancelacion')->upsert(
            [
                ['clave' => '01', 'descripcion' => 'Comprobante emitido con errores con relación', 'requiere_folio_sustitucion' => true, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '02', 'descripcion' => 'Comprobante emitido con errores sin relación', 'requiere_folio_sustitucion' => false, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '03', 'descripcion' => 'No se llevó a cabo la operación', 'requiere_folio_sustitucion' => false, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '04', 'descripcion' => 'Operación nominativa relacionada en factura global', 'requiere_folio_sustitucion' => false, 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'requiere_folio_sustitucion', 'updated_at']
        );

        DB::table('sat_objeto_imp')->upsert(
            [
                ['clave' => '01', 'descripcion' => 'No objeto de impuesto', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '02', 'descripcion' => 'Sí objeto de impuesto', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '03', 'descripcion' => 'Sí objeto de impuesto y no obligado al desglose', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '04', 'descripcion' => 'Sí objeto del impuesto y no causa impuesto', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'updated_at']
        );

        DB::table('sat_impuesto')->upsert(
            [
                ['clave' => '001', 'descripcion' => 'ISR', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '002', 'descripcion' => 'IVA', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '003', 'descripcion' => 'IEPS', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'updated_at']
        );

        DB::table('sat_tipo_factor')->upsert(
            [
                ['clave' => 'Tasa', 'descripcion' => 'Tasa', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'Cuota', 'descripcion' => 'Cuota', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'Exento', 'descripcion' => 'Exento', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'updated_at']
        );
    }

    private function importarCatalogoProductos(\Illuminate\Support\Carbon|Carbon $now): void
    {
        $path = database_path('seeders/SAT/CveSAT.csv');
        if (! is_file($path)) {
            throw new \RuntimeException("No se encontró el catálogo SAT de productos: {$path}");
        }

        $csv = new SplFileObject($path, 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
        $csv->setCsvControl(',', '"', '\\');
        $header = $csv->fgetcsv();
        $indices = $this->indicesColumnas($header ?: []);

        if (! isset($indices['clave'], $indices['descripcion'])) {
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

    private function importarCatalogoUnidades(\Illuminate\Support\Carbon|Carbon $now): void
    {
        $path = database_path('seeders/SAT/Unidades.csv');
        if (! is_file($path)) {
            throw new \RuntimeException("No se encontró el catálogo SAT de unidades: {$path}");
        }

        $csv = new SplFileObject($path, 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
        $csv->setCsvControl(',', '"', '\\');
        $header = $csv->fgetcsv();
        $indices = $this->indicesColumnas($header ?: []);

        if (! isset($indices['clave'], $indices['nombre'], $indices['unidad'])) {
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
     * @return array<string, int>
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
        while (! $csv->eof()) {
            $row = $csv->fgetcsv();
            if (! is_array($row) || count($row) < 2) {
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
