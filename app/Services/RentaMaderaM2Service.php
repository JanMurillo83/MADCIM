<?php

namespace App\Services;

use App\Enums\TipoNotaRenta;
use App\Models\Configuracion;

class RentaMaderaM2Service
{
    /**
     * Precios de renta y depósito por M2 según tipo de madera.
     * Los precios provienen de Configuracion y ya son sin IVA.
     *
     * @return array<string, array{renta: float, deposito: float}>
     */
    public static function preciosPorM2(): array
    {
        $config = Configuracion::first();

        return [
            'tabla' => [
                'renta' => (float) ($config?->imp_tabla_met ?? 0),
                'deposito' => (float) ($config?->imp_tabla_dep ?? 0),
            ],
            'triplay_15' => [
                'renta' => (float) ($config?->imp_triqui_met ?? 0),
                'deposito' => (float) ($config?->imp_triqui_dep ?? 0),
            ],
            'triplay_18' => [
                'renta' => (float) ($config?->imp_tridie_met ?? 0),
                'deposito' => (float) ($config?->imp_tridie_dep ?? 0),
            ],
        ];
    }

    public static function preciosParaTipo(TipoNotaRenta $tipo): array
    {
        $tipoMadera = $tipo->tipoMaderaM2();

        if ($tipoMadera === null) {
            return ['renta' => 0, 'deposito' => 0];
        }

        return self::preciosPorM2()[$tipoMadera] ?? ['renta' => 0, 'deposito' => 0];
    }

    /**
     * Calcula renta, depósito y totales para una cantidad de M2.
     *
     * @return array{
     *   metros: float,
     *   precio_renta_m2: float,
     *   precio_deposito_m2: float,
     *   subtotal_renta: float,
     *   iva_renta: float,
     *   total_renta: float,
     *   deposito: float,
     *   total: float,
     * }
     */
    public static function calcular(TipoNotaRenta $tipo, float $metros): array
    {
        $metros = max(0, $metros);
        $precios = self::preciosParaTipo($tipo);
        $precioRentaM2 = $precios['renta'];
        $precioDepositoM2 = $precios['deposito'];

        $subtotalRenta = round($metros * $precioRentaM2, 2);
        $ivaRenta = round($subtotalRenta * 0.16, 2);
        $totalRenta = round($subtotalRenta + $ivaRenta, 2);
        $deposito = round($metros * $precioDepositoM2, 2);
        $total = round($totalRenta + $deposito, 2);

        return [
            'metros' => $metros,
            'precio_renta_m2' => $precioRentaM2,
            'precio_deposito_m2' => $precioDepositoM2,
            'subtotal_renta' => $subtotalRenta,
            'iva_renta' => $ivaRenta,
            'total_renta' => $totalRenta,
            'deposito' => $deposito,
            'total' => $total,
        ];
    }

    public static function productoRentaM2Id(TipoNotaRenta $tipo): int
    {
        return match ($tipo) {
            TipoNotaRenta::MaderaM2Triplay15 => 146, // SRENTATRI15-M2
            TipoNotaRenta::MaderaM2Triplay18 => 145, // SRENTATRI18-M2
            default => 143, // SRENTA-M2
        };
    }
}
