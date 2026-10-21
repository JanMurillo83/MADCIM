<?php

namespace App\Services;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoIncidencia;
use App\Models\Empleado;
use App\Models\Incidencia;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class GestionarIncidenciaService
{
    public function aprobar(Incidencia $incidencia, int $usuarioId, ?string $comentario = null): Incidencia
    {
        $this->validarAutorizador($usuarioId);

        if ($incidencia->estatus !== EstadoIncidencia::Pendiente) {
            throw ValidationException::withMessages([
                'estatus' => 'Solo se pueden aprobar incidencias pendientes.',
            ]);
        }

        $incidencia->forceFill([
            'estatus' => EstadoIncidencia::Aprobada,
            'autorizada_por_id' => $usuarioId,
            'autorizada_en' => now(),
            'comentario_autorizacion' => $comentario,
        ])->save();

        return $incidencia->refresh();
    }

    public function rechazar(Incidencia $incidencia, int $usuarioId, string $comentario): Incidencia
    {
        $this->validarAutorizador($usuarioId);

        if ($incidencia->estatus !== EstadoIncidencia::Pendiente) {
            throw ValidationException::withMessages([
                'estatus' => 'Solo se pueden rechazar incidencias pendientes.',
            ]);
        }

        if (blank($comentario)) {
            throw ValidationException::withMessages([
                'comentario_autorizacion' => 'El motivo del rechazo es obligatorio.',
            ]);
        }

        $incidencia->forceFill([
            'estatus' => EstadoIncidencia::Rechazada,
            'autorizada_por_id' => $usuarioId,
            'autorizada_en' => now(),
            'comentario_autorizacion' => $comentario,
        ])->save();

        return $incidencia->refresh();
    }

    public function validarEmpleado(int $empleadoId, int $sucursalId): Empleado
    {
        $empleado = Empleado::query()->find($empleadoId);

        if (!$empleado || $empleado->estatus !== 'activo') {
            throw ValidationException::withMessages([
                'empleado_id' => 'El empleado debe existir y estar activo.',
            ]);
        }

        if ((int) $empleado->sucursal_id !== $sucursalId) {
            throw ValidationException::withMessages([
                'sucursal_id' => 'La incidencia debe pertenecer a la sucursal del empleado.',
            ]);
        }

        return $empleado;
    }

    public function afectaNomina(TipoIncidencia $tipo): bool
    {
        return in_array($tipo, [
            TipoIncidencia::PermisoSinGoce,
            TipoIncidencia::HoraExtra,
            TipoIncidencia::FaltaJustificada,
            TipoIncidencia::RetardoAutorizado,
        ], true);
    }

    private function validarAutorizador(int $usuarioId): void
    {
        $usuario = User::query()->find($usuarioId);

        if (!$usuario || !in_array($usuario->role, [
            RolePermissions::ROLE_ADMIN,
            RolePermissions::ROLE_SUPERVISOR,
            RolePermissions::ROLE_RH,
            RolePermissions::ROLE_NOMINA,
            RolePermissions::ROLE_GERENCIA,
        ], true)) {
            throw new AuthorizationException('El usuario no puede autorizar incidencias.');
        }
    }
}
