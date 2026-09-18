<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\CierreDevolucionRenta;
use App\Models\Clientes;
use App\Models\DevolucionesRenta;
use App\Models\DevolucionRentaPartidas;
use App\Models\DocumentoSerie;
use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotaDevolucionRentaPartida;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\NotaVentaVentaPartidas;
use App\Models\Pagos;
use App\Models\RegistroRenta;
use App\Services\InventarioMovimientoService;
use Illuminate\Support\Facades\DB;

class CierreDevolucionRentaService
{
    public function procesarDepositoPendiente(int $cierreId, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($cierreId, $userId): bool {
            $cierre = CierreDevolucionRenta::query()->lockForUpdate()->findOrFail($cierreId);
            if ($cierre->estatus !== 'PendienteCaja') {
                return $cierre->estatus === 'Procesado';
            }

            $nota = NotasVentaRenta::query()
                ->where('cliente_id', $cierre->cliente_id)
                ->where('direccion_entrega_id', $cierre->direccion_entrega_id)
                ->where('estatus', '!=', 'Cancelada')
                ->orderByDesc('id')
                ->firstOrFail();

            return $this->registrarDepositoPendiente($cierre, $nota, $cierre->observaciones, $userId);
        });
    }

    public function cerrarPorObra(
        int $clienteId,
        int $direccionEntregaId,
        ?string $observaciones = null,
        ?int $userId = null,
        string $modo = 'devolucion',
        ?string $folioInterno = null,
    ): array
    {
        $cierreExistente = CierreDevolucionRenta::query()
            ->where('cliente_id', $clienteId)
            ->where('direccion_entrega_id', $direccionEntregaId)
            ->lockForUpdate()
            ->first();

        if ($cierreExistente?->estatus === 'Procesado') {
            return [
                'already_closed' => true,
                'resumen' => $this->resumenDesdeCierre($cierreExistente),
                'nota_id' => NotasVentaRenta::query()
                    ->where('cliente_id', $clienteId)
                    ->where('direccion_entrega_id', $direccionEntregaId)
                    ->orderByDesc('id')
                    ->value('id'),
                'cierre_id' => $cierreExistente->id,
                'cierre_estatus' => $cierreExistente->estatus,
            ];
        }

        $nota = NotasVentaRenta::query()
            ->where('cliente_id', $clienteId)
            ->where('direccion_entrega_id', $direccionEntregaId)
            ->where('estatus', '!=', 'Cancelada')
            ->orderBy('id')
            ->firstOrFail();

        // El depósito pertenece al conjunto de rentas de la obra, no a una sola nota.
        $nota->deposito = (float) NotasVentaRenta::query()
            ->where('cliente_id', $clienteId)
            ->where('direccion_entrega_id', $direccionEntregaId)
            ->where('estatus', '!=', 'Cancelada')
            ->sum('deposito');

        if ($cierreExistente?->estatus === 'PendienteCaja') {
            $cajaUsada = $this->registrarDepositoPendiente($cierreExistente, $nota, $observaciones, $userId);

            return [
                'already_closed' => false,
                'resumen' => $this->resumenDesdeCierre($cierreExistente->fresh()),
                'nota_id' => $nota->id,
                'cierre_id' => $cierreExistente->id,
                'cierre_estatus' => $cierreExistente->fresh()->estatus,
                'caja_usada' => $cajaUsada,
            ];
        }

        $resultado = $this->cerrar($nota, $observaciones, $userId, $modo, true, $folioInterno, (float) $nota->deposito);

        NotasVentaRenta::query()
            ->where('cliente_id', $clienteId)
            ->where('direccion_entrega_id', $direccionEntregaId)
            ->where('estatus', '!=', 'Cancelada')
            ->update(['estatus' => $modo === 'venta_madera' ? 'Vendida' : 'Devuelta']);

        $totales = $resultado['resumen']['totales'];
        $cierre = CierreDevolucionRenta::create([
            'cliente_id' => $clienteId,
            'direccion_entrega_id' => $direccionEntregaId,
            'estatus' => $totales['deposito_devolver'] > 0 && empty($resultado['caja_usada'])
                ? 'PendienteCaja'
                : 'Procesado',
            'deposito_acumulado' => $totales['deposito'],
            'deposito_aplicado' => $totales['deposito_aplicado'],
            'deposito_a_devolver' => $totales['deposito_devolver'],
            'total_faltantes' => $totales['total_faltantes'],
            'saldo_por_cobrar' => $totales['saldo_por_cobrar'],
            'nota_venta_venta_id' => $resultado['nota_venta_venta_id'],
            'devolucion_renta_id' => $resultado['devolucion_renta_id'],
            'caja_movimiento_id' => $resultado['caja_movimiento_id'] ?? null,
            'observaciones' => $observaciones,
            'user_id' => $userId,
            'cerrada_en' => now(),
        ]);

        return [
            ...$resultado,
            'nota_id' => $nota->id,
            'cierre_id' => $cierre->id,
            'cierre_estatus' => $cierre->estatus,
        ];
    }

    public function obtenerResumen(NotasVentaRenta $nota): array
    {
        return $this->calcularResumen($nota->fresh(['notasEnvio.partidas.producto', 'cliente']));
    }

    public function obtenerResumenPorObra(NotasVentaRenta $nota): array
    {
        $resumen = $this->obtenerResumen($nota);
        $depositoNotas = (float) NotasVentaRenta::query()
            ->where('cliente_id', $nota->cliente_id)
            ->where('direccion_entrega_id', $nota->direccion_entrega_id)
            ->where('estatus', '!=', 'Cancelada')
            ->sum('deposito');
        $depositoRegistros = (float) RegistroRenta::query()
            ->where('cliente_id', $nota->cliente_id)
            ->whereHas('notaVentaRenta', fn ($query) => $query->where('direccion_entrega_id', $nota->direccion_entrega_id))
            ->sum('importe_deposito');
        $deposito = $depositoRegistros > 0 ? $depositoRegistros : $depositoNotas;
        $totalObra = (float) NotasVentaRenta::query()
            ->where('cliente_id', $nota->cliente_id)
            ->where('direccion_entrega_id', $nota->direccion_entrega_id)
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $resumen['totales']['deposito'] = $deposito;
        $resumen['totales']['total_renta'] = max(0, round($totalObra - $deposito, 2));
        $resumen['totales']['deposito_aplicado'] = min($deposito, $resumen['totales']['total_faltantes']);
        $resumen['totales']['saldo_por_cobrar'] = max(0, $resumen['totales']['total_faltantes'] - $resumen['totales']['deposito_aplicado']);
        $resumen['totales']['deposito_devolver'] = max(0, $deposito - $resumen['totales']['deposito_aplicado']);

        return $resumen;
    }

    public function cerrar(
        NotasVentaRenta $nota,
        ?string $observaciones = null,
        ?int $userId = null,
        string $modo = 'devolucion',
        bool $forzarCierre = false,
        ?string $folioInterno = null,
        ?float $depositoOverride = null,
    ): array
    {
        return DB::transaction(function () use ($nota, $observaciones, $userId, $modo, $forzarCierre, $folioInterno, $depositoOverride) {
            $nota = NotasVentaRenta::query()
                ->whereKey($nota->id)
                ->lockForUpdate()
                ->firstOrFail();

            $hayPendientesAcumulados = $this->tieneRegistrosAcumulados($nota)
                && RegistroRenta::query()
                    ->where('cliente_id', $nota->cliente_id)
                    ->whereHas('notaVentaRenta', fn ($query) => $query->where('direccion_entrega_id', $nota->direccion_entrega_id))
                    ->whereRaw('COALESCE(cantidad_devuelta, 0) < cantidad')
                    ->exists();

            if (!$forzarCierre && in_array($nota->estatus, ['Devuelta', 'Vendida'], true) && !$hayPendientesAcumulados) {
                $resumen = $this->calcularResumen($nota->fresh(['notasEnvio.partidas.producto', 'cliente']));
                return [
                    'already_closed' => true,
                    'resumen' => $resumen,
                ];
            }

            $nota->load(['notasEnvio.partidas.producto', 'cliente']);

            $resumen = $depositoOverride === null
                ? $this->calcularResumen($nota->fresh(['notasEnvio.partidas.producto', 'cliente']))
                : $this->aplicarDepositoAlResumen(
                    $this->calcularResumen($nota->fresh(['notasEnvio.partidas.producto', 'cliente'])),
                    $depositoOverride,
                );
            $modo = $nota->esMadera() && $modo === 'venta_madera' ? 'venta_madera' : 'devolucion';
            $referencia = 'Cierre NVR ' . ($nota->serie ?? '') . '-' . ($nota->folio ?? '');

            // Todo lo que salió en renta regresa administrativamente al inventario.
            // Si hubo una Nota de Devolución, solo se registra aquí la diferencia.
            $this->asegurarEntradaInventarioRenta($nota, $userId, $referencia);

            $notaVentaVenta = null;

            if ((float) $resumen['totales']['total_faltantes'] > 0) {
                $notaVentaVenta = (new NotasVentaVenta([
                    'cliente_id' => $nota->cliente_id,
                    'sucursal_id' => $nota->sucursal_id,
                    'user_id' => $userId,
                    'serie' => 'M',
                    'fecha_emision' => now(),
                    'condicion_pago' => 'credito',
                    'fecha_vencimiento_pago' => now()->toDateString(),
                    'moneda' => $nota->moneda ?? 'MXN',
                    'tipo_cambio' => $nota->tipo_cambio ?? 1,
                    'subtotal' => $resumen['totales']['subtotal_faltantes'],
                    'impuestos_total' => $resumen['totales']['iva_faltantes'],
                    'total' => $resumen['totales']['total_faltantes'],
                    'saldo_pendiente' => $resumen['totales']['total_faltantes'],
                    'estatus' => 'Activa',
                    'estatus_envio' => 'Entregada',
                    'forma_pago' => '99',
                    'metodo_pago' => 'PUE',
                    'documento_origen_id' => $nota->id,
                ]))->omitirValidacionEstatusCliente();
                $notaVentaVenta->save();

                foreach ($resumen['rows'] as $row) {
                    NotaVentaVentaPartidas::create([
                        'nota_venta_venta_id' => $notaVentaVenta->id,
                        'cantidad' => $row['faltante'],
                        'item' => (string) ($row['producto_id'] ?? $row['clave']),
                        'descripcion' => 'Cargo por faltante renta - ' . $row['producto'],
                        'valor_unitario' => $row['precio_unitario'],
                        'subtotal' => $row['subtotal'],
                        'impuestos' => $row['iva'],
                        'total' => $row['total'],
                    ]);
                }

                $this->registrarSalidasComoVenta($resumen['rows'], $notaVentaVenta, $referencia);

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
                'serie' => $this->resolverSerieDevolucion(),
                'fecha_emision' => now(),
                'folio_interno' => $folioInterno,
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
                    'item' => (string) ($row['producto_id'] ?? $row['clave']),
                    'descripcion' => 'Faltante - ' . $row['producto'],
                    'valor_unitario' => $row['precio_unitario'],
                    'subtotal' => $row['subtotal'],
                    'impuestos' => $row['iva'],
                    'total' => $row['total'],
                ]);
            }

            $cajaUsada = false;
            $cajaMovimientoId = null;
            if ((float) $resumen['totales']['deposito_devolver'] > 0) {
                $cajaAbierta = Caja::query()
                    ->where('estatus', 'Abierta')
                    ->when($userId, fn ($q) => $q->where('usuario_apertura_id', $userId))
                    ->first();

                if (!$cajaAbierta) {
                    $cajaAbierta = Caja::query()->where('estatus', 'Abierta')->first();
                }

                if ($cajaAbierta) {
                    $cajaMovimiento = CajaMovimiento::create([
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
                    $cajaMovimientoId = $cajaMovimiento->id;

                    $eg = $cajaAbierta->movimientos()
                        ->where('tipo', 'Egreso')
                        ->where('metodo_pago', 'Efectivo')
                        ->sum('importe');

                    $cajaAbierta->update(['total_egresos_cash' => $eg]);
                    $cajaUsada = true;
                }
            }

            if ($this->tieneRegistrosAcumulados($nota)) {
                RegistroRenta::query()
                    ->where('cliente_id', $nota->cliente_id)
                    ->whereHas('notaVentaRenta', fn ($query) => $query->where('direccion_entrega_id', $nota->direccion_entrega_id))
                    ->update([
                        'cantidad_devuelta' => DB::raw('cantidad'),
                        'estado' => 'Devuelto',
                    ]);
            }

            // La renta queda cerrada también cuando el faltante ya se convirtió
            // en venta; evita que vuelva a aparecer como devolución pendiente.
            NotaEnvioPartida::query()
                ->whereHas('notaEnvio', fn ($q) => $q->where('nota_venta_renta_id', $nota->id))
                ->update([
                    'cantidad_devuelta' => DB::raw('cantidad'),
                    'estado' => 'Devuelto',
                ]);

            NotaEnvio::query()
                ->where('nota_venta_renta_id', $nota->id)
                ->update(['estado_renta' => 'Devuelta']);

            $nota->update(['estatus' => $modo === 'venta_madera' ? 'Vendida' : 'Devuelta']);

            if ((float) $resumen['totales']['total_faltantes'] > 0) {
                Clientes::find($nota->cliente_id)?->bloquear();
            }

            return [
                'already_closed' => false,
                'resumen' => $resumen,
                'nota_venta_venta_id' => $notaVentaVenta?->id,
                'devolucion_renta_id' => $devolucion->id,
                'caja_usada' => $cajaUsada,
                'caja_movimiento_id' => $cajaMovimientoId,
                'modo' => $modo,
            ];
        });
    }

    private function asegurarEntradaInventarioRenta(NotasVentaRenta $nota, ?int $userId, string $referencia): void
    {
        if ($this->tieneRegistrosAcumulados($nota)) {
            return;
        }

        $cantidadesEnviadas = [];

        foreach ($nota->notasEnvio as $envio) {
            foreach ($envio->partidas as $partida) {
                $productoId = (int) $partida->producto_id;
                if ($productoId <= 0) {
                    continue;
                }

                $cantidadesEnviadas[$productoId] = ($cantidadesEnviadas[$productoId] ?? 0)
                    + (float) $partida->cantidad;
            }
        }

        $cantidadesAplicadas = NotaDevolucionRentaPartida::query()
            ->whereHas('notaDevolucionRenta', function ($query) use ($nota) {
                $query->where('nota_venta_renta_id', $nota->id)
                    ->where('estatus', '!=', 'Cancelada');
            })
            ->where('cantidad_aplicada', '>', 0)
            ->get(['producto_id', 'cantidad_aplicada'])
            ->groupBy('producto_id')
            ->map(fn ($partidas) => $partidas->sum(fn ($partida) => (float) $partida->cantidad_aplicada));

        foreach ($cantidadesEnviadas as $productoId => $cantidadEnviada) {
            $cantidadAplicada = (float) ($cantidadesAplicadas->get($productoId) ?? 0);
            $cantidadPendienteDeRegistrar = round($cantidadEnviada - $cantidadAplicada, 8);

            if ($cantidadPendienteDeRegistrar <= 0) {
                continue;
            }

            InventarioMovimientoService::entrada(
                productoId: $productoId,
                cantidad: $cantidadPendienteDeRegistrar,
                motivo: "Cierre de devolución de renta {$referencia}",
                documentoReferencia: $referencia,
            );
        }
    }

    private function registrarSalidasComoVenta(array $rows, NotasVentaVenta $notaVenta, string $referencia): void
    {
        foreach ($rows as $row) {
            $productoId = (int) ($row['producto_id'] ?? 0);
            $cantidad = (float) ($row['faltante'] ?? 0);

            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            InventarioMovimientoService::salida(
                productoId: $productoId,
                cantidad: $cantidad,
                motivo: "Venta por cierre de renta {$referencia}",
                documentoReferencia: $notaVenta->serie . $notaVenta->folio,
            );
        }
    }

    private function resolverSerieDevolucion(): string
    {
        $serie = (string) DocumentoSerie::query()
            ->where('documento_tipo', 'devoluciones_renta')
            ->orderBy('serie')
            ->value('serie');

        if ($serie) {
            return $serie;
        }

        DocumentoSerie::create([
            'documento_tipo' => 'devoluciones_renta',
            'serie' => 'DR',
            'descripcion' => 'Serie automática para devoluciones de renta',
            'ultimo_folio' => 0,
        ]);

        return 'DR';
    }

    private function registrarDepositoPendiente(
        CierreDevolucionRenta $cierre,
        NotasVentaRenta $nota,
        ?string $observaciones,
        ?int $userId,
    ): bool {
        if ((float) $cierre->deposito_a_devolver <= 0) {
            $cierre->update(['estatus' => 'Procesado', 'observaciones' => $observaciones ?? $cierre->observaciones]);
            return false;
        }

        $cajaAbierta = Caja::query()
            ->where('estatus', 'Abierta')
            ->when($userId, fn ($query) => $query->where('usuario_apertura_id', $userId))
            ->first()
            ?? Caja::query()->where('estatus', 'Abierta')->first();

        if (!$cajaAbierta) {
            return false;
        }

        $movimiento = CajaMovimiento::create([
            'caja_id' => $cajaAbierta->id,
            'tipo' => 'Egreso',
            'fuente' => 'Devolución depósito renta',
            'metodo_pago' => 'Efectivo',
            'importe' => $cierre->deposito_a_devolver,
            'referencia' => 'Cierre devolución obra ' . $nota->cliente_id . '-' . $cierre->direccion_entrega_id,
            'observaciones' => $observaciones ?? $cierre->observaciones,
            'user_id' => $userId,
            'fecha' => now(),
            'movimentable_type' => CierreDevolucionRenta::class,
            'movimentable_id' => $cierre->id,
        ]);

        $egresos = $cajaAbierta->movimientos()
            ->where('tipo', 'Egreso')
            ->where('metodo_pago', 'Efectivo')
            ->sum('importe');

        $cajaAbierta->update(['total_egresos_cash' => $egresos]);
        $cierre->update([
            'estatus' => 'Procesado',
            'caja_movimiento_id' => $movimiento->id,
            'observaciones' => $observaciones ?? $cierre->observaciones,
        ]);

        return true;
    }

    private function resumenDesdeCierre(CierreDevolucionRenta $cierre): array
    {
        $totalObra = (float) NotasVentaRenta::query()
            ->where('cliente_id', $cierre->cliente_id)
            ->where('direccion_entrega_id', $cierre->direccion_entrega_id)
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        return [
            'rows' => [],
            'totales' => [
                'deposito' => (float) $cierre->deposito_acumulado,
                'total_renta' => max(0, round($totalObra - (float) $cierre->deposito_acumulado, 2)),
                'subtotal_faltantes' => round((float) $cierre->total_faltantes / 1.16, 2),
                'iva_faltantes' => round((float) $cierre->total_faltantes - ((float) $cierre->total_faltantes / 1.16), 2),
                'total_faltantes' => (float) $cierre->total_faltantes,
                'deposito_aplicado' => (float) $cierre->deposito_aplicado,
                'saldo_por_cobrar' => (float) $cierre->saldo_por_cobrar,
                'deposito_devolver' => (float) $cierre->deposito_a_devolver,
            ],
        ];
    }

    private function aplicarDepositoAlResumen(array $resumen, float $deposito): array
    {
        $resumen['totales']['deposito'] = $deposito;
        $resumen['totales']['deposito_aplicado'] = min($deposito, $resumen['totales']['total_faltantes']);
        $resumen['totales']['saldo_por_cobrar'] = max(0, $resumen['totales']['total_faltantes'] - $resumen['totales']['deposito_aplicado']);
        $resumen['totales']['deposito_devolver'] = max(0, $deposito - $resumen['totales']['deposito_aplicado']);

        return $resumen;
    }

    private function calcularResumen(NotasVentaRenta $nota): array
    {
        if ($this->tieneRegistrosAcumulados($nota)) {
            return $this->calcularResumenAcumulado($nota);
        }

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
                        $producto = trim((string) ($partida->producto?->descripcion ?? $partida->descripcion ?? 'Producto'));
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
        $totalRenta = max(0, round((float) ($nota->total ?? 0) - $deposito, 2));
        $depositoAplicado = min($deposito, $totalFaltantes);
        $saldoPorCobrar = max(0, $totalFaltantes - $depositoAplicado);
        $depositoDevolver = max(0, $deposito - $depositoAplicado);

        return [
            'rows' => $rows,
            'totales' => [
                'deposito' => $deposito,
                'total_renta' => $totalRenta,
                'subtotal_faltantes' => $subtotalFaltantes,
                'iva_faltantes' => $ivaFaltantes,
                'total_faltantes' => $totalFaltantes,
                'deposito_aplicado' => $depositoAplicado,
                'saldo_por_cobrar' => $saldoPorCobrar,
                'deposito_devolver' => $depositoDevolver,
            ],
        ];
    }

    private function tieneRegistrosAcumulados(NotasVentaRenta $nota): bool
    {
        return $nota->cliente_id
            && $nota->direccion_entrega_id
            && RegistroRenta::query()
                ->where('cliente_id', $nota->cliente_id)
                ->whereHas('notaVentaRenta', fn ($query) => $query->where('direccion_entrega_id', $nota->direccion_entrega_id))
                ->exists();
    }

    private function calcularResumenAcumulado(NotasVentaRenta $nota): array
    {
        $registros = RegistroRenta::query()
            ->with('producto')
            ->where('cliente_id', $nota->cliente_id)
            ->whereHas('notaVentaRenta', fn ($query) => $query->where('direccion_entrega_id', $nota->direccion_entrega_id))
            ->get()
            ->groupBy('producto_id');

        $rows = [];
        foreach ($registros as $productoId => $registrosProducto) {
            $registro = $registrosProducto->first();
            if (($registro->producto?->clave ?? '') === 'SRENTA-M2') {
                continue;
            }

            $cantidad = (float) $registrosProducto->sum('cantidad');
            $devuelta = (float) $registrosProducto->sum(fn ($item) => (float) ($item->cantidad_devuelta ?? 0));
            $faltante = max(0, $cantidad - $devuelta);
            if ($faltante <= 0) {
                continue;
            }

            $precioUnitario = (float) ($registro->producto?->precio_venta ?? 0);
            $subtotal = round($faltante * $precioUnitario, 2);
            $iva = round($subtotal * 0.16, 2);

            $rows[] = [
                'producto_id' => (int) $productoId,
                'clave' => (string) ($registro->producto?->clave ?? 'SIN-CLAVE'),
                'producto' => (string) ($registro->producto?->descripcion ?? 'Producto'),
                'faltante' => $faltante,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => round($subtotal + $iva, 2),
            ];
        }

        $subtotalFaltantes = round(array_sum(array_column($rows, 'subtotal')), 2);
        $ivaFaltantes = round(array_sum(array_column($rows, 'iva')), 2);
        $totalFaltantes = round(array_sum(array_column($rows, 'total')), 2);
        $deposito = (float) ($nota->deposito ?? 0);
        $totalRenta = max(0, round((float) ($nota->total ?? 0) - $deposito, 2));
        $depositoAplicado = min($deposito, $totalFaltantes);

        return [
            'rows' => $rows,
            'totales' => [
                'deposito' => $deposito,
                'total_renta' => $totalRenta,
                'subtotal_faltantes' => $subtotalFaltantes,
                'iva_faltantes' => $ivaFaltantes,
                'total_faltantes' => $totalFaltantes,
                'deposito_aplicado' => $depositoAplicado,
                'saldo_por_cobrar' => max(0, $totalFaltantes - $depositoAplicado),
                'deposito_devolver' => max(0, $deposito - $depositoAplicado),
            ],
        ];
    }
}
