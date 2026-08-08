<?php

namespace App\Support;

class Numero
{
    /**
     * Formatea un número con 2 decimales, separador de miles ',' y decimal '.'.
     * Ejemplo: 1234.5 -> "1,234.50"
     */
    public static function formato(float|int|string|null $valor, int $decimales = 2): string
    {
        if ($valor === null || $valor === '') {
            $valor = 0;
        }

        return number_format((float) $valor, $decimales, '.', ',');
    }

    /**
     * Formatea un número como moneda MXN/USD con prefijo $.
     */
    public static function moneda(float|int|string|null $valor, int $decimales = 2): string
    {
        return '$' . self::formato($valor, $decimales);
    }

    /**
     * Redondea un valor a 2 decimales.
     */
    public static function redondear(float|int|string|null $valor, int $decimales = 2): float
    {
        if ($valor === null || $valor === '') {
            $valor = 0;
        }

        return round((float) $valor, $decimales);
    }
}
