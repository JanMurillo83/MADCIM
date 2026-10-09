<?php

namespace Tests\Feature;

use App\Enums\TipoNotaRenta;
use App\Services\ProductosImportService;
use App\Services\RentaMaderaM2Service;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ResetCompleteSystemTest extends TestCase
{
    use DatabaseTransactions;

    public function test_full_reset_replaces_products_and_resets_operational_balances(): void
    {
        $clientId = $this->insertClient('CLI-RESET-COMPLETE', 'Moroso', 900);
        $blockedClientId = $this->insertClient('CLI-RESET-BLOCKED', 'Bloqueado', 125);
        $supplierId = $this->insertSupplier();
        $oldProductId = DB::table('productos')->insertGetId([
            'clave' => 'OLD-PRODUCT',
            'descripcion' => 'Producto anterior',
            'grupo' => 'ANTERIOR',
            'linea' => 'ANTERIOR',
            'existencia' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notas_venta_renta')->insert([
            'cliente_id' => $clientId,
            'serie' => 'R',
            'folio' => '18',
            'fecha_emision' => now(),
            'total' => 125,
            'saldo_pendiente' => 125,
            'estatus' => 'Activa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recepcionId = DB::table('recepciones_compra')->insertGetId([
            'proveedor_id' => $supplierId,
            'total' => 450,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cuentas_por_pagar')->insert([
            'proveedor_id' => $supplierId,
            'recepcion_compra_id' => $recepcionId,
            'importe' => 450,
            'saldo_pendiente' => 450,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('historial_precios_madera')->insert([
            'lote_id' => (string) \Illuminate\Support\Str::uuid(),
            'producto_id' => $oldProductId,
            'tipo' => 'madera',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cajaId = DB::table('cajas')->insertGetId([
            'nombre' => 'Caja de reinicio',
            'fecha_apertura' => now(),
            'saldo_inicial_cash' => 650,
            'estatus' => 'Abierta',
            'total_ingresos_cash' => 400,
            'total_egresos_cash' => 30,
            'total_diferencia' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('caja_movimientos')->insert([
            'caja_id' => $cajaId,
            'tipo' => 'Ingreso',
            'importe' => 400,
            'fecha' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('documento_series')->insert([
            'documento_tipo' => 'prueba',
            'serie' => 'X',
            'ultimo_folio' => 88,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $precioConfigurado = DB::table('configuracion')->value('imp_tabla_met');

        $path = $this->createCatalogWorkbook();

        try {
            $this->artisan('sistema:reiniciar-completo', [
                'catalogo' => $path,
                '--force' => true,
            ])
                ->expectsOutputToContain('Productos cargados: 206.')
                ->assertExitCode(0);
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseMissing('productos', ['clave' => 'OLD-PRODUCT']);
        $this->assertDatabaseCount('productos', 206);
        $this->assertSame(0, DB::table('productos')->where('existencia', '!=', 0)->count());
        $this->assertDatabaseHas('productos', [
            'clave' => 'DEPOGARANTIA',
            'grupo' => 'DEPOSITO',
            'linea' => 'DEPOSITOS EN GARANTIA',
            'existencia' => 0,
        ]);
        $this->assertDatabaseHas('grupos', ['nombre' => 'PISTOLA']);
        $this->assertDatabaseHas('productos', [
            'clave' => 'POLIN-250',
            'producto_base_id' => DB::table('productos')->where('clave', 'POLINENTERO')->value('id'),
        ]);
        $this->assertNotSame(143, RentaMaderaM2Service::productoRentaM2Id(TipoNotaRenta::MaderaM2Tabla));
        $this->assertSame(
            DB::table('productos')->where('clave', 'SRENTA-M2')->value('id'),
            RentaMaderaM2Service::productoRentaM2Id(TipoNotaRenta::MaderaM2Tabla),
        );

        $this->assertDatabaseMissing('clientes', ['id' => $clientId]);
        $this->assertDatabaseMissing('clientes', ['id' => $blockedClientId]);
        $this->assertDatabaseMissing('proveedores', ['id' => $supplierId]);
        $this->assertDatabaseCount('notas_venta_renta', 0);
        $this->assertDatabaseCount('cuentas_por_pagar', 0);
        $this->assertDatabaseCount('recepciones_compra', 0);
        $this->assertDatabaseCount('historial_precios_madera', 0);
        $this->assertDatabaseCount('caja_movimientos', 0);
        $this->assertDatabaseHas('cajas', [
            'id' => $cajaId,
            'estatus' => 'Cerrada',
            'saldo_inicial_cash' => 0,
            'total_ingresos_cash' => 0,
            'total_egresos_cash' => 0,
            'total_diferencia' => 0,
            'fecha_apertura' => null,
            'usuario_apertura_id' => null,
        ]);
        $this->assertDatabaseHas('documento_series', [
            'documento_tipo' => 'prueba',
            'serie' => 'X',
            'ultimo_folio' => 0,
        ]);
        $this->assertSame($precioConfigurado, DB::table('configuracion')->value('imp_tabla_met'));
    }

    public function test_invalid_product_catalog_does_not_reset_existing_data(): void
    {
        DB::table('productos')->insert([
            'clave' => 'KEEP-ON-FAILURE',
            'descripcion' => 'Debe permanecer',
            'grupo' => 'PRUEBA',
            'linea' => 'PRUEBA',
            'existencia' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->createCatalogWorkbook(false);

        try {
            $this->artisan('sistema:reiniciar-completo', [
                'catalogo' => $path,
                '--force' => true,
            ])
                ->expectsOutputToContain('El catalogo recibido debe contener exactamente 205 productos.')
                ->assertExitCode(1);
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('productos', [
            'clave' => 'KEEP-ON-FAILURE',
            'existencia' => 4,
        ]);
    }

    public function test_full_reset_uses_repository_catalog_when_no_path_is_given(): void
    {
        $this->artisan('sistema:reiniciar-completo', ['--no-interaction' => true])
            ->expectsOutputToContain('Productos cargados: 206.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('productos', 206);
        $this->assertSame(0, DB::table('productos')->where('existencia', '!=', 0)->count());
        $this->assertDatabaseHas('productos', ['clave' => 'DEPOGARANTIA']);
    }

    public function test_product_import_normalizes_unicode_grouping_spaces_in_excel_prices(): void
    {
        $path = $this->createCatalogWorkbook(false, true);

        try {
            $products = app(ProductosImportService::class)->prepareRowsFromPath($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame(5000.0, $products[0]['precio_renta_mes']);
    }

    public function test_product_import_keeps_support_for_utf8_bom_csv_headers(): void
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'madcim-products-');
        if ($temporaryPath === false) {
            throw new \RuntimeException('No se pudo crear el archivo temporal de prueba.');
        }
        $path = $temporaryPath.'.csv';
        @unlink($temporaryPath);

        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el archivo temporal de prueba.');
        }
        fputcsv($handle, ["\xEF\xBB\xBFclave", 'descripcion', 'grupo', 'linea']);
        fputcsv($handle, ['PRODUCTO-CSV', 'Producto CSV', 'PRUEBA', 'EQUIPO']);
        fclose($handle);

        try {
            $products = app(ProductosImportService::class)->prepareRowsFromPath($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame('PRODUCTO-CSV', $products[0]['clave']);
        $this->assertSame(0.0, $products[0]['existencia']);
    }

    private function insertClient(string $key, string $status, float $balance): int
    {
        return DB::table('clientes')->insertGetId([
            'clave' => $key,
            'nombre' => 'Cliente de prueba',
            'rfc' => 'XAXX010101000',
            'regimen' => '616',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'interior' => '',
            'colonia' => 'Centro',
            'municipio' => 'Mexico',
            'estado' => 'CDMX',
            'pais' => 'Mexico',
            'telefono' => '5555555555',
            'correo' => 'cliente@example.com',
            'contacto' => 'Contacto',
            'saldo' => $balance,
            'estatus_cliente' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSupplier(): int
    {
        return DB::table('proveedores')->insertGetId([
            'clave' => 'PROV-RESET-COMPLETE',
            'nombre' => 'Proveedor de prueba',
            'rfc' => 'XAXX010101000',
            'regimen' => '616',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'interior' => '',
            'colonia' => 'Centro',
            'municipio' => 'Mexico',
            'estado' => 'CDMX',
            'pais' => 'Mexico',
            'telefono' => '5555555555',
            'correo' => 'proveedor@example.com',
            'contacto' => 'Contacto',
            'saldo' => 450,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCatalogWorkbook(bool $complete = true, bool $thinSpacePrice = false): string
    {
        $headers = [
            'Clave',
            'Producto',
            'Invetario Inicial',
            'M2 que cubre',
            'Precio Renta x Dia, Pieza o M2',
            'Renta Equipo x Semana',
            'Renta Equipo x Mes',
            'Precio de Venta',
            'Grupo',
            'Linea',
        ];

        $products = [
            ['SRENTA-M2', 'Renta madera por M2', 'RENTA', 'MADERA'],
            ['SRENTATRI15-M2', 'Renta triplay 15 por M2', 'RENTA', 'MADERA'],
            ['SRENTATRI18-M2', 'Renta triplay 18 por M2', 'RENTA', 'MADERA'],
            ['POLINENTERO', 'Polin entero', 'POLIN', 'MADERA'],
            ['POLIN-3060', 'Polin de 30 a 60 cm', 'POLIN', 'MADERA'],
            ['BARROTE-ENTERO', 'Barrote entero', 'BARROTE', 'MADERA'],
            ['BARROTE-80100', 'Barrote de 80 a 100 cm', 'BARROTE', 'MADERA'],
            ['TABLA30-ENTERA', 'Tabla de 30 entera', 'TABLA', 'MADERA'],
            ['TABLA25-ENTERA', 'Tabla de 25 entera', 'TABLA', 'MADERA'],
            ['TABLA20-ENTERA', 'Tabla de 20 entera', 'TABLA', 'MADERA'],
            ['TABLA15-ENTERA', 'Tabla de 15 entera', 'TABLA', 'MADERA'],
            ['DUELA10-ENTERA', 'Duela entera', 'DUELA', 'MADERA'],
            ['POLIN-250', 'Polin de 250 cm', 'POLIN', 'MADERA'],
        ];

        if ($complete) {
            for ($index = count($products); $index < 205; $index++) {
                $products[] = ['PROD-'.$index, 'Producto '.$index, 'PISTOLA', 'EQUIPO'];
            }
        }

        $rows = [$headers];
        foreach ($products as [$key, $description, $group, $line]) {
            $monthlyPrice = $thinSpacePrice && $key === 'SRENTA-M2'
                ? "5\u{202F}000.00"
                : 0;

            $rows[] = [
                $key,
                $description,
                12,
                0,
                100,
                0,
                $monthlyPrice,
                0,
                $group,
                $line,
            ];
        }

        $path = tempnam(sys_get_temp_dir(), 'madcim-catalog-');
        if ($path === false) {
            throw new \RuntimeException('No se pudo crear el archivo temporal de prueba.');
        }

        $xlsxPath = $path.'.xlsx';
        @unlink($path);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        $spreadsheet->getActiveSheet()
            ->getStyle('G2')
            ->getNumberFormat()
            ->setFormatCode('#,##0.00;(#,##0.00);-');
        (new Xlsx($spreadsheet))->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();

        return $xlsxPath;
    }
}
