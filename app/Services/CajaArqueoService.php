<?php

namespace App\Services;

use App\Models\Caja;
use Illuminate\Support\Facades\DB;

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
            ->when($caja->fecha_apertura, fn ($query) => $query->where('fecha', '>=', $caja->fecha_apertura))
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
        ];
    }

    public function abrir(Caja $caja, float|int|string $saldoInicial, ?int $usuarioId): Caja
    {
        return DB::transaction(function () use ($caja, $saldoInicial, $usuarioId): Caja {
            $caja = Caja::query()->lockForUpdate()->findOrFail($caja->id);

            $caja->update([
                'saldo_inicial_cash' => $saldoInicial,
                'total_ingresos_cash' => 0,
                'total_egresos_cash' => 0,
                'efectivo_teorico' => 0,
                'efectivo_contado' => 0,
                'denominaciones_efectivo' => null,
                'total_diferencia' => 0,
                'observaciones_cierre' => null,
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
    public function cerrar(Caja $caja, array $denominaciones, ?string $observaciones, ?int $usuarioId): array
    {
        return DB::transaction(function () use ($caja, $denominaciones, $observaciones, $usuarioId): array {
            $caja = Caja::query()->lockForUpdate()->findOrFail($caja->id);
            $resumen = $this->resumen($caja);
            $efectivoContado = $this->efectivoContado($denominaciones);
            $diferencia = round($efectivoContado - $resumen['efectivo_teorico'], 2);

            $caja->update([
                'total_ingresos_cash' => $resumen['ingresos_efectivo'],
                'total_egresos_cash' => $resumen['egresos_efectivo'],
                'efectivo_teorico' => $resumen['efectivo_teorico'],
                'efectivo_contado' => $efectivoContado,
                'denominaciones_efectivo' => $denominaciones,
                'total_diferencia' => $diferencia,
                'observaciones_cierre' => $observaciones,
                'estatus' => 'Cerrada',
                'fecha_cierre' => now(),
                'usuario_cierre_id' => $usuarioId,
            ]);

            return [
                ...$resumen,
                'efectivo_contado' => $efectivoContado,
                'diferencia' => $diferencia,
            ];
        });
    }
}
