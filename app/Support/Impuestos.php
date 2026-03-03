<?php

namespace App\Support;

class Impuestos
{
    public const IVA_TASA = 0.16;

    /**
     * Desglosa un importe que ya incluye IVA.
     *
     * @return array{subtotal: float, iva: float}
     */
    public static function desglosarIvaIncluido(float $totalConIva, float $tasa = self::IVA_TASA, int $precision = 2): array
    {
        $totalConIva = round($totalConIva, $precision);

        if ($totalConIva <= 0) {
            return [
                'subtotal' => 0.0,
                'iva' => 0.0,
            ];
        }

        $factor = 1 + $tasa;
        $subtotal = round($totalConIva / $factor, $precision);
        $iva = round($totalConIva - $subtotal, $precision);

        return [
            'subtotal' => $subtotal,
            'iva' => $iva,
        ];
    }
}
