<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clientes extends Model
{
    protected $fillable = ['clave','nombre','rfc','regimen','codigo','calle','exterior','interior','colonia',
    'municipio','estado','pais','telefono','correo','descuento','lista','contacto','dias_credito','saldo'];

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
        $this->save();
    }
}
