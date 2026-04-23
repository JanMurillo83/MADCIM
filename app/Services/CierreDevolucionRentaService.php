<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\DevolucionesRenta;
use App\Models\DevolucionRentaPartidas;
use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\NotaVentaVentaPartidas;
use App\Models\Pagos;
use Illuminate\Support\Facades\DB;

class CierreDevolucionRentaService
{
    public function obtenerResumen(NotasVentaRenta $nota): array
    {
        return $this->calcularResumen($nota->fresh(['notasEnvio.partidas.producto', 'cliente']));
    }

    public function cerrar(NotasVentaRenta $nota, ?string $observaciones = null, ?int $userId = null): array
    {
        return DB::transaction(function () use ($nota, $observaciones, $userId) {
            $nota = NotasVentaRenta::query()
                ->whereKey($nota->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($nota->estatus === 'Devuelta') {
                $resumen = $this->calcularResumen($nota->fresh(['notasEnvio.partidas.producto', 'cliente']));
                return [
                    'already_closed' => true,
                    'resumen' => $resumen,
                ];
            }

            $resumen = $this->calcularResumen($nota->fresh(['notasEnvio.partidas.producto', 'cliente']));

            $notaVentaVenta = null;

            if ((float) $resumen['totales']['total_faltantes'] > 0) {
                $notaVentaVenta = NotasVentaVenta::create([
                    'cliente_id' => $nota->cliente_id,
                    'sucursal_id' => $nota->sucursal_id,
                    'user_id' => $userId,
                    'serie' => 'M',
                    'fecha_emision' => now(),
                    'moneda' => $nota->moneda ?? 'MXN',
                    'tipo_cambio' => $nota->tipo_cambio ?? 1,
                    'subtotal' => $resumen['totales']['subtotal_faltantes'],
                    'impuestos_total' => $resumen['totales']['iva_faltantes'],
                    'total' => $resumen['totales']['total_faltantes'],
                    'saldo_pendiente' => $resumen['totales']['total_faltantes'],
                    'estatus' => 'Activa',
                    'estatus_envio' => 'Pendiente de Envío',
                    'forma_pago' => '99',
                    'metodo_pago' => 'PUE',
                    'documento_origen_id' => $nota->id,
                ]);

                foreach ($resumen['rows'] as $row) {
                    NotaVentaVentaPartidas::create([
                        'nota_venta_venta_id' => $notaVentaVenta->id,
                        'cantidad' => $row['faltante'],
                        'item' => $row['producto'],
                        'descripcion' => 'Cargo por faltante renta - ' . $row['producto'],
                        'valor_unitario' => $row['precio_unitario'],
                        'subtotal' => $row['subtotal'],
                        'impuestos' => $row['iva'],
                        'total' => $row['total'],
                    ]);
                }

                if ((float) $resumen['totales']['deposito_aplicado'] > 0) {
                    Pagos::create([
                        'documento_tipo' => 'notas_venta_venta',
                        'documento_id' => $notaVentaVenta->id,
                        'cliente_id' => $nota->cliente_id,
                        'fecha_pago' => now()->toDateString(),
                        'fecha_pago_hora' => now(),
                        'fecha_emision' => now(),
                        'forma_pago' => '99',
                        'metodo_pago' => 'PUE',
                        'moneda' => $nota->moneda ?? 'MXN',
                        'tipo_cambio' => $nota->tipo_cambio ?? 1,
                        'importe' => $resumen['totales']['deposito_aplicado'],
                        'referencia' => 'Aplicación de depósito NVR ' . ($nota->serie ?? '') . '-' . ($nota->folio ?? ''),
                        'observaciones' => $observaciones,
                        'user_id' => $userId,
                    ]);
                }
            }

            $devolucion = DevolucionesRenta::create([
                'serie' => 'DR',
                'fecha_emision' => now(),
                'moneda' => $nota->moneda ?? 'MXN',
                'tipo_cambio' => $nota->tipo_cambio ?? 1,
                'subtotal' => $resumen['totales']['subtotal_faltantes'],
                'impuestos_total' => $resumen['totales']['iva_faltantes'],
                'total' => $resumen['totales']['total_faltantes'],
                'estatus' => 'Aplicada',
                'documento_origen_id' => $nota->id,
            ]);

            foreach ($resumen['rows'] as $row) {
                DevolucionRentaPartidas::create([
                    'devolucion_renta_id' => $devolucion->id,
                    'cantidad' => $row['faltante'],
                    'item' => $row['producto'],
                    'descripcion' => 'Faltante - ' . $row['producto'],
                    'valor_unitario' => $row['precio_unitario'],
                    'subtotal' => $row['subtotal'],
                    'impuestos' => $row['iva'],
                    'total' => $row['total'],
                ]);
            }

            $cajaUsada = false;
            if ((float) $resumen['totales']['deposito_devolver'] > 0) {
                $cajaAbierta = Caja::query()
                    ->where('estatus', 'Abierta')
                    ->when($userId, fn ($q) => $q->where('usuario_apertura_id', $userId))
                    ->first();

                if (!$cajaAbierta) {
                    $cajaAbierta = Caja::query()->where('estatus', 'Abierta')->first();
                }

                if ($cajaAbierta) {
                    CajaMovimiento::create([
                        'caja_id' => $cajaAbierta->id,
                        'tipo' => 'Egreso',
                        'fuente' => 'Devolución depósito renta',
                        'metodo_pago' => 'Efectivo',
                        'importe' => $resumen['totales']['deposito_devolver'],
                        'referencia' => 'Cierre devolución NVR ' . ($nota->serie ?? '') . '-' . ($nota->folio ?? ''),
                        'observaciones' => $observaciones,
                        'user_id' => $userId,
                        'fecha' => now(),
                        'movimentable_type' => DevolucionesRenta::class,
                        'movimentable_id' => $devolucion->id,
                    ]);

                    $eg = $cajaAbierta->movimientos()
                        ->where('tipo', 'Egreso')
                        ->where('metodo_pago', 'Efectivo')
                        ->sum('importe');

                    $cajaAbierta->update(['total_egresos_cash' => $eg]);
                    $cajaUsada = true;
                }
            }

            NotaEnvioPartida::query()
                ->whereHas('notaEnvio', fn ($q) => $q->where('nota_venta_renta_id', $nota->id))
                ->update([
                    'cantidad_devuelta' => DB::raw('cantidad'),
                    'estado' => 'Devuelto',
                ]);

            NotaEnvio::query()
                ->where('nota_venta_renta_id', $nota->id)
                ->update(['estado_renta' => 'Devuelta']);

            $nota->update(['estatus' => 'Devuelta']);

            return [
                'already_closed' => false,
                'resumen' => $resumen,
                'nota_venta_venta_id' => $notaVentaVenta?->id,
                'devolucion_renta_id' => $devolucion->id,
                'caja_usada' => $cajaUsada,
            ];
        });
    }

    private function calcularResumen(NotasVentaRenta $nota): array
    {
        $rowsByKey = [];

        $nota->loadMissing(['notasEnvio.partidas.producto', 'cliente']);

        foreach ($nota->notasEnvio as $envio) {
            foreach ($envio->partidas as $partida) {
                if (($partida->producto?->clave ?? '') === 'SRENTA-M2') {
                    continue;
                }

                $cantidad = (float) $partida->cantidad;
                $devuelta = (float) $partida->cantidad_devuelta;
                $faltante = max(0, $cantidad - $devuelta);

                if ($faltante <= 0) {
                    continue;
                }

                $productoId = (int) ($partida->producto_id ?? 0);
                $clave = trim((string) ($partida->producto?->clave ?? 'SIN-CLAVE'));
                $producto = trim((string) ($partida->producto?->descripcion ?? $partida->descripcion ?? 'Item'));
                $precioUnitario = (float) ($partida->producto?->precio_venta ?? 0);

                $key = $productoId > 0 ? 'prod-' . $productoId : mb_strtolower($clave . '|' . $producto);

                if (!isset($rowsByKey[$key])) {
                    $rowsByKey[$key] = [
                        'producto_id' => $productoId > 0 ? $productoId : null,
                        'clave' => $clave,
                        'producto' => $producto,
                        'faltante' => 0.0,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => 0.0,
                        'iva' => 0.0,
                        'total' => 0.0,
                    ];
                }

                $rowsByKey[$key]['faltante'] += $faltante;
                $rowsByKey[$key]['precio_unitario'] = $precioUnitario;
            }
        }

        foreach ($rowsByKey as &$row) {
            $row['subtotal'] = round($row['faltante'] * $row['precio_unitario'], 2);
            $row['iva'] = round($row['subtotal'] * 0.16, 2);
            $row['total'] = round($row['subtotal'] + $row['iva'], 2);
        }
        unset($row);

        $rows = array_values($rowsByKey);

        $subtotalFaltantes = round(array_sum(array_column($rows, 'subtotal')), 2);
        $ivaFaltantes = round(array_sum(array_column($rows, 'iva')), 2);
        $totalFaltantes = round(array_sum(array_column($rows, 'total')), 2);

        $deposito = (float) ($nota->deposito ?? 0);
        $depositoAplicado = min($deposito, $totalFaltantes);
        $saldoPorCobrar = max(0, $totalFaltantes - $depositoAplicado);
        $depositoDevolver = max(0, $deposito - $depositoAplicado);

        return [
            'rows' => $rows,
            'totales' => [
                'deposito' => $deposito,
                'subtotal_faltantes' => $subtotalFaltantes,
                'iva_faltantes' => $ivaFaltantes,
                'total_faltantes' => $totalFaltantes,
                'deposito_aplicado' => $depositoAplicado,
                'saldo_por_cobrar' => $saldoPorCobrar,
                'deposito_devolver' => $depositoDevolver,
            ],
        ];
    }
}
