<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ResetOperationalDataService
{
    private const REQUIRED_PRODUCT_KEYS = [
        'SRENTA-M2',
        'SRENTATRI15-M2',
        'SRENTATRI18-M2',
        'POLINENTERO',
        'POLIN-3060',
        'BARROTE-ENTERO',
        'BARROTE-80100',
        'TABLA30-ENTERA',
        'TABLA25-ENTERA',
        'TABLA20-ENTERA',
        'TABLA15-ENTERA',
        'DUELA10-ENTERA',
        'DEPOGARANTIA',
    ];

    private const PRODUCT_BASE_FAMILIES = [
        'POLINENTERO' => ['POLIN-'],
        'BARROTE-ENTERO' => ['BARROTE-'],
        'TABLA30-ENTERA' => ['TABLA30-'],
        'TABLA25-ENTERA' => ['TABLA25-'],
        'TABLA20-ENTERA' => ['TABLA20-'],
        'TABLA15-ENTERA' => ['TABLA15-'],
        'DUELA10-ENTERA' => ['DUELA-'],
    ];

    private const TABLES_TO_RESET = [
        'cfdi_pago_impuestos',
        'cfdi_pago_doctos',
        'cfdi_partida_impuestos',
        'cfdi_relacionados',
        'cierres_devolucion_renta',
        'historial_precios_madera',
        'movimientos_inventario',
        'nota_venta_renta_m2_desglose',
        'nota_devolucion_renta_partidas',
        'notas_devolucion_renta',
        'nota_envio_partidas',
        'notas_envio',
        'embarque_items',
        'embarques',
        'caja_movimientos',
        'pagos',
        'cuentas_por_pagar',
        'recepcion_compra_partidas',
        'recepciones_compra',
        'orden_compra_partidas',
        'ordenes_compra',
        'requisicion_compra_partidas',
        'requisiciones_compra',
        'registro_rentas',
        'devolucion_renta_partidas',
        'devoluciones_renta',
        'devolucion_venta_partidas',
        'devoluciones_venta',
        'factura_cfdi_partidas',
        'facturas_cfdi',
        'nota_venta_renta_partidas',
        'notas_venta_renta',
        'nota_venta_venta_partidas',
        'notas_venta_venta',
        'cotizacion_partidas',
        'cotizaciones',
    ];

    public function reset(): int
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            throw new AuthorizationException('Solo un administrador puede reiniciar los datos.');
        }

        return $this->resetTables();
    }

    public function resetFromCommand(): int
    {
        return $this->resetTables();
    }

    public function resetWithProductCatalog(string $path, ProductosImportService $importer): array
    {
        if (! Schema::hasTable('productos') || ! Schema::hasColumn('productos', 'producto_base_id')) {
            throw new RuntimeException('El esquema de productos no tiene la relacion de producto base requerida.');
        }

        if (! Schema::hasTable('grupos') || ! Schema::hasTable('lineas')) {
            throw new RuntimeException('No se encontraron los catalogos de grupos y lineas.');
        }

        $products = $importer->prepareRowsFromPath($path);
        $productsByKey = [];

        foreach ($products as $product) {
            $key = mb_strtoupper(trim((string) $product['clave']));

            if (isset($productsByKey[$key])) {
                throw new RuntimeException("La clave de producto {$key} aparece mas de una vez en el archivo.");
            }

            $product['clave'] = trim((string) $product['clave']);
            $product['existencia'] = 0;
            $productsByKey[$key] = $product;
        }

        if (count($productsByKey) !== 205) {
            throw new RuntimeException('El catalogo recibido debe contener exactamente 205 productos.');
        }

        $legacyDeposit = null;
        if (! isset($productsByKey['DEPOGARANTIA'])) {
            $legacyPath = public_path('csvdata/productos.csv');
            $legacyProducts = $importer->prepareRowsFromPath($legacyPath);
            foreach ($legacyProducts as $product) {
                if (mb_strtoupper(trim((string) $product['clave'])) === 'DEPOGARANTIA') {
                    $legacyDeposit = $product;
                    break;
                }
            }

            if ($legacyDeposit === null) {
                throw new RuntimeException('No se encontro el producto reservado DEPOGARANTIA en el catalogo legado.');
            }

            $legacyDeposit['grupo'] = 'DEPOSITO';
            $legacyDeposit['linea'] = 'DEPOSITOS EN GARANTIA';
            $legacyDeposit['existencia'] = 0;
            $productsByKey['DEPOGARANTIA'] = $legacyDeposit;
        }

        $missingKeys = array_diff(self::REQUIRED_PRODUCT_KEYS, array_keys($productsByKey));
        if ($missingKeys !== []) {
            throw new RuntimeException('Faltan productos requeridos por el sistema: '.implode(', ', $missingKeys).'.');
        }

        $products = array_values($productsByKey);
        $tablesReset = $this->resetTables($products, $importer);

        return [
            'tables' => $tablesReset,
            'products' => count($products),
        ];
    }

    private function resetTables(?array $replacementProducts = null, ?ProductosImportService $importer = null): int
    {
        if ($replacementProducts !== null && $importer === null) {
            throw new RuntimeException('Se requiere el importador para reemplazar el catalogo de productos.');
        }

        return DB::transaction(function () use ($replacementProducts, $importer): int {
            $tablesToReset = self::TABLES_TO_RESET;
            if ($replacementProducts !== null) {
                $tablesToReset[] = 'productos';
            }

            $tables = array_values(array_filter(
                $tablesToReset,
                static fn (string $table): bool => Schema::hasTable($table),
            ));

            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            try {
                foreach ($tables as $table) {
                    DB::table($table)->delete();
                }

                $this->resetCustomerBalances();
                $this->resetSupplierBalances();

                if (Schema::hasTable('documento_series')) {
                    DB::table('documento_series')->update([
                        'ultimo_folio' => 0,
                    ]);
                }

                if (Schema::hasTable('cajas')) {
                    DB::table('cajas')->update([
                        'fecha_apertura' => null,
                        'usuario_apertura_id' => null,
                        'saldo_inicial_cash' => 0,
                        'estatus' => 'Cerrada',
                        'fecha_cierre' => null,
                        'usuario_cierre_id' => null,
                        'total_ingresos_cash' => 0,
                        'total_egresos_cash' => 0,
                        'total_diferencia' => 0,
                        'efectivo_teorico' => 0,
                        'efectivo_contado' => 0,
                        'denominaciones_efectivo' => null,
                        'observaciones_cierre' => null,
                        'updated_at' => now(),
                    ]);
                }

                if ($replacementProducts !== null) {
                    $this->ensureProductCatalogValues($replacementProducts);
                    $importer?->importPreparedRows($replacementProducts);
                    $this->restoreWoodProductBaseRelations();
                }
            } finally {
                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                }
            }

            return count($tables);
        });
    }

    private function resetCustomerBalances(): void
    {
        if (! Schema::hasTable('clientes') || ! Schema::hasColumn('clientes', 'saldo')) {
            return;
        }

        $updates = ['saldo' => 0];
        if (Schema::hasColumn('clientes', 'updated_at')) {
            $updates['updated_at'] = now();
        }
        DB::table('clientes')->update($updates);

        if (Schema::hasColumn('clientes', 'estatus_cliente')) {
            $statusUpdates = ['estatus_cliente' => 'Activo'];
            if (Schema::hasColumn('clientes', 'updated_at')) {
                $statusUpdates['updated_at'] = now();
            }
            DB::table('clientes')
                ->where('estatus_cliente', 'Moroso')
                ->update($statusUpdates);
        }
    }

    private function resetSupplierBalances(): void
    {
        if (! Schema::hasTable('proveedores') || ! Schema::hasColumn('proveedores', 'saldo')) {
            return;
        }

        $updates = ['saldo' => 0];
        if (Schema::hasColumn('proveedores', 'updated_at')) {
            $updates['updated_at'] = now();
        }
        DB::table('proveedores')->update($updates);
    }

    private function ensureProductCatalogValues(array $products): void
    {
        foreach (['grupos' => 'grupo', 'lineas' => 'linea'] as $table => $field) {
            $values = array_unique(array_map(
                static fn (array $product): string => trim((string) $product[$field]),
                $products,
            ));

            foreach ($values as $value) {
                if (! DB::table($table)->where('nombre', $value)->exists()) {
                    DB::table($table)->insert(['nombre' => $value]);
                }
            }
        }
    }

    private function restoreWoodProductBaseRelations(): void
    {
        foreach (self::PRODUCT_BASE_FAMILIES as $baseKey => $prefixes) {
            $baseId = DB::table('productos')->where('clave', $baseKey)->value('id');
            if ($baseId === null) {
                throw new RuntimeException("No se pudo restaurar el producto base {$baseKey}.");
            }

            foreach ($prefixes as $prefix) {
                DB::table('productos')
                    ->where('linea', 'MADERA')
                    ->where('clave', 'like', $prefix.'%')
                    ->whereNull('producto_base_id')
                    ->update(['producto_base_id' => $baseId]);
            }
        }
    }
}
