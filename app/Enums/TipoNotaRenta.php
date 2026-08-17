<?php

namespace App\Enums;

enum TipoNotaRenta: string
{
    case Equipo = 'equipo';
    case MaderaPieza = 'madera_pieza';
    case MaderaM2Tabla = 'madera_m2_tabla';
    case MaderaM2Triplay15 = 'madera_m2_triplay_15';
    case MaderaM2Triplay18 = 'madera_m2_triplay_18';

    public function label(): string
    {
        return match ($this) {
            self::Equipo => 'Renta de Equipo',
            self::MaderaPieza => 'Renta de Madera por Pieza',
            self::MaderaM2Tabla => 'Renta de Madera por M2 (Tabla)',
            self::MaderaM2Triplay15 => 'Renta de Madera por M2 (Triplay 15mm)',
            self::MaderaM2Triplay18 => 'Renta de Madera por M2 (Triplay 18mm)',
        };
    }

    public function esMadera(): bool
    {
        return $this !== self::Equipo;
    }

    public function esMaderaM2(): bool
    {
        return in_array($this, [self::MaderaM2Tabla, self::MaderaM2Triplay15, self::MaderaM2Triplay18], true);
    }

    public function tipoMaderaM2(): ?string
    {
        return match ($this) {
            self::MaderaM2Tabla => 'tabla',
            self::MaderaM2Triplay15 => 'triplay_15',
            self::MaderaM2Triplay18 => 'triplay_18',
            default => null,
        };
    }

    public function tipoMaderaParaDesglose(): ?string
    {
        return match ($this) {
            self::MaderaPieza => 'pieza',
            self::MaderaM2Tabla => 'tabla',
            self::MaderaM2Triplay15 => 'triplay_15',
            self::MaderaM2Triplay18 => 'triplay_18',
            default => null,
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

    /**
     * @return array<string, string>
     */
    public static function optionsM2(): array
    {
        return [
            self::MaderaM2Tabla->value => self::MaderaM2Tabla->label(),
            self::MaderaM2Triplay15->value => self::MaderaM2Triplay15->label(),
            self::MaderaM2Triplay18->value => self::MaderaM2Triplay18->label(),
        ];
    }

    public static function labelFor(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return self::tryFrom($value)?->label() ?? $value;
    }
}
