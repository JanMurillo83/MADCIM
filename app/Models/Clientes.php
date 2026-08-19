<?php

namespace App\Models;

use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clientes extends Model
{
    protected $fillable = ['clave','nombre','rfc','curp','ine','regimen','codigo','calle','exterior','interior','colonia',
    'municipio','estado','pais','telefono','correo','descuento','lista','contacto','dias_credito','saldo',
    'estatus_cliente','desbloqueo_discrecional'];

    protected $casts = [
        'desbloqueo_discrecional' => 'boolean',
    ];

    public const ESTATUS_ACTIVO = 'Activo';
    public const ESTATUS_MOROSO = 'Moroso';
    public const ESTATUS_BLOQUEADO = 'Bloqueado';

    public function direccionesEntrega(): HasMany
    {
        return $this->hasMany(ClienteDireccionEntrega::class, 'cliente_id');
    }

    public function direccionesEntregaActivas(): HasMany
    {
        return $this->hasMany(ClienteDireccionEntrega::class, 'cliente_id')
            ->where('activa', true);
    }

    public function notasVentaRenta(): HasMany
    {
        return $this->hasMany(NotasVentaRenta::class, 'cliente_id');
    }

    public function notasVentaVenta(): HasMany
    {
        return $this->hasMany(NotasVentaVenta::class, 'cliente_id');
    }

    /**
     * Recalcula el saldo del cliente en base a:
     * - Total de notas de renta y venta no canceladas.
     * - Total de facturas CFDI no canceladas.
     * - Total de pagos registrados.
     */
    public function recalcularSaldo(): void
    {
        $totalNotasRenta = (float) $this->notasVentaRenta()
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $totalNotasVenta = (float) $this->notasVentaVenta()
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $totalFacturas = (float) FacturasCfdi::query()
            ->where('cliente_id', $this->id)
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $totalPagos = (float) Pagos::query()
            ->where('cliente_id', $this->id)
            ->sum('importe');

        $nuevoSaldo = max(0, $totalNotasRenta + $totalNotasVenta + $totalFacturas - $totalPagos);

        $this->saldo = $nuevoSaldo;
        $this->actualizarEstatusPorVencimiento();
        $this->saveQuietly();
    }

    public function tieneCuentaVencida(?CarbonInterface $hoy = null): bool
    {
        $hoy = ($hoy ?? now())->toDateString();

        if ($this->notasVentaRenta()
            ->where('estatus', '!=', 'Cancelada')
            ->where('condicion_pago', 'credito')
            ->where('saldo_pendiente', '>', 0)
            ->whereDate('fecha_vencimiento_pago', '<', $hoy)
            ->exists()) {
            return true;
        }

        if ($this->notasVentaVenta()
            ->where('estatus', '!=', 'Cancelada')
            ->where('condicion_pago', 'credito')
            ->where('saldo_pendiente', '>', 0)
            ->whereDate('fecha_vencimiento_pago', '<', $hoy)
            ->exists()) {
            return true;
        }

        return FacturasCfdi::query()
            ->where('cliente_id', $this->id)
            ->where('estatus', '!=', 'Cancelada')
            ->where('saldo_pendiente', '>', 0)
            ->get(['fecha_emision'])
            ->contains(function (FacturasCfdi $factura) use ($hoy): bool {
                if (!$factura->fecha_emision) {
                    return false;
                }

                return $factura->fecha_emision
                    ->copy()
                    ->addDays(max(0, (int) $this->dias_credito))
                    ->toDateString() < $hoy;
            });
    }

    public function actualizarEstatusPorVencimiento(?CarbonInterface $hoy = null): void
    {
        if ($this->estatus_cliente === self::ESTATUS_BLOQUEADO) {
            return;
        }

        $estatus = $this->tieneCuentaVencida($hoy)
            ? self::ESTATUS_MOROSO
            : self::ESTATUS_ACTIVO;

        $this->estatus_cliente = $estatus;
    }

    public function puedeCrearNota(string $condicionPago): bool
    {
        if ($this->estatus_cliente === self::ESTATUS_BLOQUEADO) {
            return false;
        }

        return $this->estatus_cliente !== self::ESTATUS_MOROSO
            || $condicionPago === 'contado';
    }

    public function validarCreacionNota(string $condicionPago): void
    {
        if ($this->estatus_cliente === self::ESTATUS_BLOQUEADO) {
            throw new DomainException('El cliente está bloqueado y no puede recibir Notas de Venta o Renta.');
        }

        if ($this->estatus_cliente === self::ESTATUS_MOROSO && $condicionPago !== 'contado') {
            throw new DomainException('El cliente está moroso. Solo se permiten operaciones de contado.');
        }

        if ($condicionPago === 'credito' && (int) $this->dias_credito <= 0) {
            throw new DomainException('El cliente no tiene días de crédito configurados.');
        }
    }

    public function bloquear(): void
    {
        $this->forceFill(['estatus_cliente' => self::ESTATUS_BLOQUEADO])->saveQuietly();
    }

    public function desbloquear(): void
    {
        if ((float) $this->saldo > 0 && !$this->desbloqueo_discrecional) {
            throw new DomainException('El cliente solo puede desbloquearse cuando su saldo sea 0.');
        }

        $this->forceFill(['estatus_cliente' => self::ESTATUS_ACTIVO])->saveQuietly();
    }
}
