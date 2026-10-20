<?php

namespace App\Enums;

enum MetodoChecada: string
{
    case Huella = 'huella';
    case Facial = 'facial';
    case Nip = 'nip';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Huella => 'Huella',
            self::Facial => 'Reconocimiento facial',
            self::Nip => 'NIP',
            self::Manual => 'Captura manual',
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
