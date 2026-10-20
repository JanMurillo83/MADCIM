<?php

namespace App\Services;

use App\Enums\MetodoChecada;
use App\Enums\TipoChecada;
use App\Models\Checada;
use App\Models\Empleado;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RegistrarChecadaService
{
    /**
     * @param array{
     *     empleado_id: int,
     *     sucursal_id: int,
     *     registrada_en?: Carbon|string,
     *     tipo: TipoChecada|string,
     *     metodo: MetodoChecada|string,
     *     dispositivo?: string|null,
     *     identificador_externo?: string|null,
     *     autorizada_por_id?: int|null,
     *     motivo_autorizacion?: string|null,
     *     observacion?: string|null
     * } $datos
     */
    public function registrar(array $datos): Checada
    {
        $empleado = Empleado::query()->find($datos['empleado_id']);

        if (!$empleado) {
            throw ValidationException::withMessages([
                'empleado_id' => 'El empleado indicado no existe.',
            ]);
        }

        if ($empleado->estatus !== 'activo') {
            throw ValidationException::withMessages([
                'empleado_id' => 'El empleado no tiene estatus activo.',
            ]);
        }

        if ((int) $empleado->sucursal_id !== (int) $datos['sucursal_id']) {
            throw ValidationException::withMessages([
                'sucursal_id' => 'La checada debe pertenecer a la sucursal del empleado.',
            ]);
        }

        $tipo = $datos['tipo'] instanceof TipoChecada
            ? $datos['tipo']
            : TipoChecada::tryFrom((string) $datos['tipo']);
        $metodo = $datos['metodo'] instanceof MetodoChecada
            ? $datos['metodo']
            : MetodoChecada::tryFrom((string) $datos['metodo']);

        if (!$tipo || !$metodo) {
            throw ValidationException::withMessages([
                'tipo' => 'El tipo o método de checada no es válido.',
            ]);
        }

        return Checada::create([
            ...$datos,
            'tipo' => $tipo,
            'metodo' => $metodo,
            'registrada_en' => isset($datos['registrada_en'])
                ? Carbon::parse($datos['registrada_en'])
                : now(),
        ]);
    }
}
