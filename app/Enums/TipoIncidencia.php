<?php

namespace App\Enums;

enum TipoIncidencia: string
{
    case RetardoAutorizado = 'retardo_autorizado';
    case PermisoConGoce = 'permiso_con_goce';
    case PermisoSinGoce = 'permiso_sin_goce';
    case Incapacidad = 'incapacidad';
    case HoraExtra = 'hora_extra';
    case FaltaJustificada = 'falta_justificada';
    case DescansoLaborado = 'descanso_laborado';
    case SalidaAutorizada = 'salida_autorizada';

    public function label(): string
    {
        return match ($this) {
            self::RetardoAutorizado => 'Retardo autorizado',
            self::PermisoConGoce => 'Permiso con goce',
            self::PermisoSinGoce => 'Permiso sin goce',
            self::Incapacidad => 'Incapacidad',
            self::HoraExtra => 'Hora extra',
            self::FaltaJustificada => 'Falta justificada',
            self::DescansoLaborado => 'Descanso laborado',
            self::SalidaAutorizada => 'Salida autorizada',
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
