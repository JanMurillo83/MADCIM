<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\CajaCorte;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\DevolucionesRenta;
use App\Models\DevolucionesVenta;
use App\Models\Pagos;
use App\Enums\TipoNotaRenta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CajaArqueoService
{
    /** @return array{monedas: array<string, float>, billetes: array<string, float>} */
    public function denominaciones(): array
    {
        return [
            'monedas' => [
                'moneda_0_5' => 0.50,
                'moneda_1' => 1.00,
                'moneda_2' => 2.00,
                'moneda_5' => 5.00,
                'moneda_10' => 10.00,
                'moneda_20' => 20.00,
            ],
            'billetes' => [
                'billete_20' => 20.00,
                'billete_50' => 50.00,
                'billete_100' => 100.00,
                'billete_200' => 200.00,
                'billete_500' => 500.00,
                'billete_1000' => 1000.00,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function resumen(Caja $caja): array
    {
        $movimientos = $caja->movimientos()
            ->visibleEnCaja()
            ->when(
                $caja->corte_id,
                fn ($query) => $query->where(function ($query) use ($caja): void {
                    $query->where('corte_id', $caja->corte_id)
                        ->when($caja->fecha_apertura, function ($query) use ($caja): void {
                            $query->orWhere(fn ($query) => $query
                                ->whereNull('corte_id')
                                ->where('fecha', '>=', $caja->fecha_apertura));
                        }, function ($query): void {
                            $query->orWhereNull('corte_id');
                        });
                }),
            )
            ->when(
                ! $caja->corte_id && $caja->fecha_apertura,
                fn ($query) => $query->where('fecha', '>=', $caja->fecha_apertura),
            )
            ->get();
        $ingresosEfectivo = (float) $movimientos
            ->where('tipo', 'Ingreso')
            ->where('metodo_pago', 'Efectivo')
            ->sum('importe');
        $egresosEfectivo = (float) $movimientos
            ->where('tipo', 'Egreso')
            ->where('metodo_pago', 'Efectivo')
            ->sum('importe');

        $desglose = $movimientos
            ->groupBy(fn ($movimiento): string => $movimiento->metodo_pago ?: 'Sin especificar')
            ->map(function ($items): array {
                return [
                    'ingresos' => (float) $items->where('tipo', 'Ingreso')->sum('importe'),
                    'egresos' => (float) $items->where('tipo', 'Egreso')->sum('importe'),
                ];
            })
            ->all();

        return [
            'saldo_inicial' => (float) ($caja->saldo_inicial_cash ?? 0),
            'ingresos_efectivo' => $ingresosEfectivo,
            'egresos_efectivo' => $egresosEfectivo,
            'efectivo_teorico' => (float) ($caja->saldo_inicial_cash ?? 0) + $ingresosEfectivo - $egresosEfectivo,
            'desglose' => $desglose,
            'movimientos_no_efectivo' => $movimientos
                ->where('tipo', 'Ingreso')
                ->reject(fn ($movimiento): bool => in_array(mb_strtolower((string) $movimiento->metodo_pago), ['efectivo', 'crédito', 'credito'], true))
                ->map(fn ($movimiento): array => [
                    'movimiento_id' => $movimiento->id,
                    'metodo_pago' => $movimiento->metodo_pago ?: 'Sin especificar',
                    'referencia' => $movimiento->referencia,
                    'importe' => (float) $movimiento->importe,
                ])->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function reporteCierre(Caja $caja, array $bancos = []): array
    {
        $inicio = $caja->fecha_apertura ?? $caja->created_at;
        $fin = now();
        $categorias = ['Madera', 'Carpintería', 'Rentas de madera', 'Renta de Equipos', 'Ferretería', 'Matto. A equipos'];
        $empty = array_fill_keys($categorias, 0.0);
        $ventas = $empty;
        $credito = $empty;
        $devoluciones = $empty;
        $recuperados = $empty;

        $ventasVenta = NotasVentaVenta::withoutGlobalScopes()->with('partidas.producto')
            ->where('sucursal_id', $caja->sucursal_id)->whereBetween('fecha_emision', [$inicio, $fin])
            ->where('estatus', '!=', 'Cancelada')->get();
        foreach ($ventasVenta as $nota) {
            $this->sumarPartidas($ventas, $nota->partidas, false);
            if ($nota->condicion_pago === 'credito' || $nota->cobro_credito_confirmado_en) {
                $this->sumarPartidas($credito, $nota->partidas, false);
            }
        }

        $ventasRenta = NotasVentaRenta::withoutGlobalScopes()->with('partidas.producto')
            ->where('sucursal_id', $caja->sucursal_id)->whereBetween('fecha_emision', [$inicio, $fin])
            ->where('estatus', '!=', 'Cancelada')->get();
        foreach ($ventasRenta as $nota) {
            $this->sumarPartidas($ventas, $nota->partidas, true);
            if ($nota->condicion_pago === 'credito' || $nota->cobro_credito_confirmado_en) {
                $this->sumarPartidas($credito, $nota->partidas, true);
            }
        }

        $devolucionVenta = DevolucionesVenta::withoutGlobalScopes()->with('partidas.producto')
            ->where('sucursal_id', $caja->sucursal_id)->whereBetween('fecha_emision', [$inicio, $fin])
            ->where('estatus', '!=', 'Cancelada')->get();
        foreach ($devolucionVenta as $devolucion) {
            $this->sumarPartidas($devoluciones, $devolucion->partidas, false);
        }

        $devolucionRenta = DevolucionesRenta::withoutGlobalScopes()->with('partidas.producto')
            ->where('sucursal_id', $caja->sucursal_id)->whereBetween('fecha_emision', [$inicio, $fin])
            ->where('estatus', '!=', 'Cancelada')->get();
        foreach ($devolucionRenta as $devolucion) {
            $this->sumarPartidas($devoluciones, $devolucion->partidas, true);
        }

        $pagosCredito = Pagos::query()->where('caja_id', $caja->id)
            ->whereBetween('fecha_pago_hora', [$inicio, $fin])
            ->whereIn('documento_tipo', ['notas_venta_venta', 'notas_venta_renta'])->get();
        foreach ($pagosCredito as $pago) {
            $nota = $pago->documento_tipo === 'notas_venta_renta'
                ? NotasVentaRenta::withoutGlobalScopes()->with('partidas.producto')->find($pago->documento_id)
                : NotasVentaVenta::withoutGlobalScopes()->with('partidas.producto')->find($pago->documento_id);
            if (! $nota || ! $nota->cobro_credito_confirmado_en) {
                continue;
            }
            $this->sumarPartidas($recuperados, $nota->partidas, $pago->documento_tipo === 'notas_venta_renta', (float) $pago->importe, (float) $nota->total);
        }

        $depositos = collect($bancos)->filter(fn (array $banco): bool => filled($banco['banco'] ?? null))
            ->groupBy(fn (array $banco): string => ($banco['banco'] ?? '') . '|' . ($banco['metodo_pago'] ?? ''))
            ->map(function ($filas): array {
                $primero = $filas->first();
                return ['banco' => $primero['banco'], 'metodo_pago' => $primero['metodo_pago'], 'importe' => round((float) $filas->sum('importe'), 2)];
            })->values()->all();

        return [
            'ventas_totales' => $this->roundCategorias($ventas),
            'devoluciones' => $this->roundCategorias($devoluciones),
            'ventas_credito' => $this->roundCategorias($credito),
            'ventas_contado' => round(array_sum($ventas) - array_sum($credito) - array_sum($devoluciones), 2),
            'creditos_recuperados' => $this->roundCategorias($recuperados),
            'bancos' => $depositos,
            'bancos_total' => round(array_sum(array_column($depositos, 'importe')), 2),
            'salidas_efectivo' => (float) $caja->movimientos()->visibleEnCaja()->where('tipo', 'Egreso')->where('metodo_pago', 'Efectivo')->sum('importe'),
            'fondo_fijo' => (float) ($caja->saldo_inicial_cash ?? 0),
        ];
    }

    private function sumarPartidas(array &$totales, $partidas, bool $renta, ?float $importe = null, ?float $totalDocumento = null): void
    {
        $lineas = [];
        foreach ($partidas as $partida) {
            $categoria = $this->categoria($partida, $renta);
            if ($categoria !== null) {
                $lineas[$categoria] = ($lineas[$categoria] ?? 0) + (float) ($partida->subtotal ?? 0) + (float) ($partida->impuestos ?? 0);
            }
        }
        $factor = $importe !== null && $totalDocumento > 0 ? $importe / $totalDocumento : 1;
        foreach ($lineas as $categoria => $valor) {
            if (array_key_exists($categoria, $totales)) {
                $totales[$categoria] += $valor * $factor;
            }
        }
    }

    private function categoria($partida, bool $renta): ?string
    {
        if ($renta) {
            $tipo = TipoNotaRenta::tryFrom((string) ($partida->tipo_nota_renta ?? $partida->documento?->tipo_nota_renta ?? ''));
            if ($tipo) {
                return $tipo->esMadera() ? 'Rentas de madera' : 'Renta de Equipos';
            }
            return mb_strtolower((string) ($partida->producto?->linea ?? '')) === 'madera' ? 'Rentas de madera' : 'Renta de Equipos';
        }
        return match (mb_strtolower(trim((string) ($partida->producto?->linea ?? '')))) {
            'madera' => 'Madera',
            'carpinteria', 'carpintería' => 'Carpintería',
            'ferreteria', 'ferretería' => 'Ferretería',
            'mantenimiento', 'matto. a equipos', 'mantenimiento a equipos' => 'Matto. A equipos',
            default => null,
        };
    }

    private function roundCategorias(array $categorias): array
    {
        return array_map(fn ($importe): float => round((float) $importe, 2), $categorias);
    }

    public function abrir(Caja $caja, float|int|string $saldoInicial, ?int $usuarioId): Caja
    {
        return DB::transaction(function () use ($caja, $saldoInicial, $usuarioId): Caja {
            $caja = Caja::query()->lockForUpdate()->findOrFail($caja->id);

            $caja->update([
                'saldo_inicial_cash' => $saldoInicial,
                'corte_id' => (string) Str::uuid(),
                'total_ingresos_cash' => 0,
                'total_egresos_cash' => 0,
                'efectivo_teorico' => 0,
                'efectivo_contado' => 0,
                'denominaciones_efectivo' => null,
                'total_diferencia' => 0,
                'observaciones_cierre' => null,
                'reporte_cierre' => null,
                'estatus' => 'Abierta',
                'fecha_apertura' => now(),
                'fecha_cierre' => null,
                'usuario_apertura_id' => $usuarioId,
                'usuario_cierre_id' => null,
            ]);

            return $caja->fresh();
        });
    }

    public function efectivoContado(array $denominaciones): float
    {
        $total = 0.0;
        foreach ($this->denominaciones() as $grupo) {
            foreach ($grupo as $clave => $valor) {
                $total += max(0, (int) ($denominaciones[$clave] ?? 0)) * $valor;
            }
        }

        return round($total, 2);
    }

    /** @return array{efectivo_teorico: float, efectivo_contado: float, diferencia: float} */
    public function cerrar(Caja $caja, array $denominaciones, ?string $observaciones, ?int $usuarioId, array $bancos = []): array
    {
        return DB::transaction(function () use ($caja, $denominaciones, $observaciones, $usuarioId, $bancos): array {
            $caja = Caja::query()->lockForUpdate()->findOrFail($caja->id);
            $resumen = $this->resumen($caja);
            $bancos = $this->validarAsignacionBancos($resumen['movimientos_no_efectivo'], $bancos);
            $efectivoContado = $this->efectivoContado($denominaciones);
            $diferencia = round($efectivoContado - $resumen['efectivo_teorico'], 2);
            $reporteCierre = $this->reporteCierre($caja, $bancos);

            $caja->update([
                'total_ingresos_cash' => $resumen['ingresos_efectivo'],
                'total_egresos_cash' => $resumen['egresos_efectivo'],
                'efectivo_teorico' => $resumen['efectivo_teorico'],
                'efectivo_contado' => $efectivoContado,
                'denominaciones_efectivo' => $denominaciones,
                'total_diferencia' => $diferencia,
                'observaciones_cierre' => $observaciones,
                'reporte_cierre' => $reporteCierre,
                'estatus' => 'Cerrada',
                'fecha_cierre' => now(),
                'usuario_cierre_id' => $usuarioId,
            ]);

            CajaCorte::create([
                'corte_id' => $caja->corte_id,
                'caja_id' => $caja->id,
                'sucursal_id' => $caja->sucursal_id,
                'fecha_apertura' => $caja->fecha_apertura,
                'fecha_cierre' => $caja->fecha_cierre,
                'usuario_apertura_id' => $caja->usuario_apertura_id,
                'usuario_cierre_id' => $caja->usuario_cierre_id,
                'saldo_inicial_cash' => $caja->saldo_inicial_cash,
                'total_ingresos_cash' => $caja->total_ingresos_cash,
                'total_egresos_cash' => $caja->total_egresos_cash,
                'efectivo_teorico' => $caja->efectivo_teorico,
                'efectivo_contado' => $caja->efectivo_contado,
                'total_diferencia' => $caja->total_diferencia,
                'denominaciones_efectivo' => $caja->denominaciones_efectivo,
                'observaciones_cierre' => $caja->observaciones_cierre,
                'reporte_cierre' => $caja->reporte_cierre,
            ]);

            return [
                ...$resumen,
                'efectivo_contado' => $efectivoContado,
                'diferencia' => $diferencia,
                'reporte_cierre' => $reporteCierre,
            ];
        });
    }

    private function validarAsignacionBancos(array $movimientos, array $bancos): array
    {
        $asignaciones = collect($bancos)->keyBy(fn (array $fila): int => (int) ($fila['movimiento_id'] ?? 0));
        foreach ($movimientos as $movimiento) {
            $asignacion = $asignaciones->get((int) $movimiento['movimiento_id']);
            if (! $asignacion || ! filled($asignacion['banco'] ?? null)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['depositos_banco' => 'Asigna el banco o cuenta de depósito a todos los cobros que no fueron en efectivo.']);
            }
        }

        $idsEsperados = collect($movimientos)->pluck('movimiento_id')->map(fn ($id): int => (int) $id)->sort()->values();
        $idsRecibidos = $asignaciones->keys()->map(fn ($id): int => (int) $id)->filter()->sort()->values();
        if ($idsEsperados->all() !== $idsRecibidos->all()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['depositos_banco' => 'Los cobros cambiaron durante el cierre. Cierra esta ventana y vuelve a abrirla para actualizar la lista.']);
        }

        return collect($movimientos)->map(function (array $movimiento) use ($asignaciones): array {
            return [
                ...$movimiento,
                'banco' => trim((string) $asignaciones->get((int) $movimiento['movimiento_id'])['banco']),
            ];
        })->all();
    }
}
