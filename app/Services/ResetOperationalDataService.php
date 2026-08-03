<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetOperationalDataService
{
    private const TABLES_TO_RESET = [
        'cfdi_pago_impuestos',
        'cfdi_pago_doctos',
        'cfdi_partida_impuestos',
        'cfdi_relacionados',
        'nota_devolucion_renta_partidas',
        'notas_devolucion_renta',
        'nota_envio_partidas',
        'notas_envio',
        'embarque_items',
        'embarques',
        'caja_movimientos',
        'pagos',
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
        'cajas',
        'cliente_direcciones_entrega',
        'documento_series',
        'lineas',
        'grupos',
    ];

    public function reset(): int
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            throw new AuthorizationException('Solo un administrador puede reiniciar los datos.');
        }

        $tables = array_values(array_filter(
            self::TABLES_TO_RESET,
            static fn (string $table): bool => Schema::hasTable($table),
        ));

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            foreach ($tables as $table) {
                if ($driver === 'mysql') {
                    DB::statement('TRUNCATE TABLE ' . $table);
                } else {
                    DB::table($table)->delete();
                }
            }
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        return count($tables);
    }
}
