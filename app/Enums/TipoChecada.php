<?php

namespace App\Enums;

enum TipoChecada: string
{
    case Entrada = 'entrada';
    case SalidaComida = 'salida_comida';
    case RegresoComida = 'regreso_comida';
    case Salida = 'salida';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::SalidaComida => 'Salida a comida',
            self::RegresoComida => 'Regreso de comida',
            self::Salida => 'Salida',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
