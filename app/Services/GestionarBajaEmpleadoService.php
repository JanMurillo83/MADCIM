<?php

namespace App\Services;

use App\Models\BajaEmpleado;
use App\Models\Empleado;
use App\Models\MovimientoLaboral;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionarBajaEmpleadoService
{
    public function registrar(BajaEmpleado $baja): BajaEmpleado
    {
        return DB::transaction(function () use ($baja): BajaEmpleado {
            $empleado = Empleado::query()->lockForUpdate()->findOrFail($baja->empleado_id);

            if ($empleado->estatus === 'baja') {
                throw ValidationException::withMessages([
                    'empleado_id' => 'El empleado ya se encuentra dado de baja.',
                ]);
            }

            $baja->save();
            $empleado->update(['estatus' => 'baja']);

            return $baja->refresh();
        });
    }

    public function reingresar(Empleado $empleado, string $fecha, ?string $motivo = null): MovimientoLaboral
    {
        return DB::transaction(function () use ($empleado, $fecha, $motivo): MovimientoLaboral {
            $empleado = Empleado::query()->lockForUpdate()->findOrFail($empleado->id);

            if ($empleado->estatus !== 'baja') {
                throw ValidationException::withMessages([
                    'empleado_id' => 'Solo se pueden reingresar empleados con estatus de baja.',
                ]);
            }

            $empleado->update(['estatus' => 'activo']);

            return MovimientoLaboral::create([
                'empleado_id' => $empleado->id,
                'fecha_movimiento' => $fecha,
                'tipo' => 'reingreso',
                'sucursal_destino_id' => $empleado->sucursal_id,
                'departamento' => $empleado->departamento,
                'puesto' => $empleado->puesto,
                'horario_id' => $empleado->horario_id,
                'motivo' => $motivo ?: 'Reingreso del empleado.',
            ]);
        });
    }
}
