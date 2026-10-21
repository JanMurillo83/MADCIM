<?php
namespace App\Services;
use App\Enums\EstadoVacacion;
use App\Models\SaldoVacacion;
use App\Models\SolicitudVacacion;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
class GestionarVacacionService
{
    public function aprobar(SolicitudVacacion $solicitud, int $usuarioId): SolicitudVacacion
    {
        $this->autorizador($usuarioId);
        if ($solicitud->estatus !== EstadoVacacion::Pendiente) throw ValidationException::withMessages(['estatus' => 'La solicitud ya fue resuelta.']);
        \DB::transaction(function () use ($solicitud, $usuarioId): void {
            $saldo = SaldoVacacion::query()->lockForUpdate()->findOrFail($solicitud->saldo_vacaciones_id);
            if ($saldo->dias_disponibles < (float) $solicitud->dias) {
                throw ValidationException::withMessages(['dias' => 'El saldo de vacaciones es insuficiente.']);
            }
            $saldo->increment('dias_usados', $solicitud->dias);
            $solicitud->forceFill(['estatus' => EstadoVacacion::Aprobada, 'autorizada_por_id' => $usuarioId, 'autorizada_en' => now()])->save();
        });
        return $solicitud->refresh();
    }
    public function rechazar(SolicitudVacacion $solicitud, int $usuarioId, string $comentario): SolicitudVacacion
    {
        $this->autorizador($usuarioId);
        if ($solicitud->estatus !== EstadoVacacion::Pendiente) throw ValidationException::withMessages(['estatus' => 'La solicitud ya fue resuelta.']);
        if (blank($comentario)) throw ValidationException::withMessages(['comentario_autorizacion' => 'El motivo del rechazo es obligatorio.']);
        return tap($solicitud)->forceFill(['estatus' => EstadoVacacion::Rechazada, 'autorizada_por_id' => $usuarioId, 'autorizada_en' => now(), 'comentario_autorizacion' => $comentario])->save();
    }
    private function autorizador(int $id): void
    {
        $usuario = User::find($id);
        if (!$usuario || !in_array($usuario->role, [RolePermissions::ROLE_ADMIN, RolePermissions::ROLE_SUPERVISOR, RolePermissions::ROLE_RH, RolePermissions::ROLE_NOMINA, RolePermissions::ROLE_GERENCIA], true)) throw new AuthorizationException('El usuario no puede autorizar vacaciones.');
    }
}
