<?php

namespace App\Enums;

enum TipoDocumento: string
{
    case Cotizacion = 'cotizacion';
    case NotaVentaRenta = 'nota_venta_renta';
    case NotaVentaVenta = 'nota_venta_venta';
    case FacturaCfdi = 'factura_cfdi';
    case DevolucionRenta = 'devolucion_renta';
    case DevolucionVenta = 'devolucion_venta';

    public function label(): string
    {
        return match ($this) {
            self::Cotizacion => 'Cotizacion',
            self::NotaVentaRenta => 'Nota de venta (renta)',
            self::NotaVentaVenta => 'Nota de venta (venta)',
            self::FacturaCfdi => 'Factura CFDI',
            self::DevolucionRenta => 'Devolucion renta',
            self::DevolucionVenta => 'Devolucion venta',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function labelFor(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return self::tryFrom($value)?->label() ?? $value;
    }
}
