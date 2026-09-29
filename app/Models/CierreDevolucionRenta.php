<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreDevolucionRenta extends Model
{
    use BelongsToSucursalScope;

    protected $table = 'cierres_devolucion_renta';

    protected $fillable = [
        'sucursal_id',
        'cliente_id',
        'direccion_entrega_id',
        'nota_ids',
        'estatus',
        'deposito_acumulado',
        'deposito_aplicado',
        'deposito_a_devolver',
        'total_faltantes',
        'saldo_por_cobrar',
        'nota_venta_venta_id',
        'devolucion_renta_id',
        'caja_movimiento_id',
        'observaciones',
        'user_id',
        'cerrada_en',
    ];

    protected $casts = [
        'sucursal_id' => 'integer',
        'deposito_acumulado' => 'decimal:2',
        'deposito_aplicado' => 'decimal:2',
        'deposito_a_devolver' => 'decimal:2',
        'total_faltantes' => 'decimal:2',
        'saldo_por_cobrar' => 'decimal:2',
        'cerrada_en' => 'datetime',
        'nota_ids' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $cierre): void {
            $user = auth()->user();
            $notaIds = collect($cierre->nota_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
            $sucursales = NotasVentaRenta::withoutGlobalScope('sucursal')->whereIn('id', $notaIds)->pluck('sucursal_id')->unique();

            if ($sucursales->count() > 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sucursal_id' => 'No se puede cerrar una obra que incluye notas de distintas sucursales.',
                ]);
            }

            $sucursalOrigen = $sucursales->first();
            if ($sucursalOrigen && $cierre->sucursal_id && (int) $cierre->sucursal_id !== (int) $sucursalOrigen) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sucursal_id' => 'El cierre debe pertenecer a la sucursal de las rentas asociadas.',
                ]);
            }
            if ($user && !$user->isAdmin()) {
                if (!$user->sucursal_id) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'sucursal_id' => 'Tu usuario debe tener una sucursal asignada para procesar cierres.',
                    ]);
                }

                if ($sucursalOrigen && (int) $sucursalOrigen !== (int) $user->sucursal_id) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'sucursal_id' => 'No puedes cerrar rentas de otra sucursal.',
                    ]);
                }
            }

            $cierre->sucursal_id ??= $sucursalOrigen ?? $user?->sucursal_id;
        });

        static::updating(function (self $cierre): void {
            $user = auth()->user();
            if ($user && !$user->isAdmin() && (!$user->sucursal_id || (int) $cierre->getOriginal('sucursal_id') !== (int) $user->sucursal_id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sucursal_id' => 'No puedes modificar cierres de otra sucursal.',
                ]);
            }

            if ($user && !$user->isAdmin()) {
                $notaIds = collect($cierre->nota_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
                $sucursales = NotasVentaRenta::withoutGlobalScope('sucursal')
                    ->whereIn('id', $notaIds)
                    ->pluck('sucursal_id')
                    ->unique();

                if ($sucursales->count() > 1 || ($sucursales->isNotEmpty() && (int) $sucursales->first() !== (int) $user->sucursal_id)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'nota_ids' => 'No puedes asociar un cierre a rentas de otra sucursal.',
                    ]);
                }
            }
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function direccionEntrega(): BelongsTo
    {
        return $this->belongsTo(ClienteDireccionEntrega::class, 'direccion_entrega_id');
    }

    public function notaVentaVenta(): BelongsTo
    {
        return $this->belongsTo(NotasVentaVenta::class, 'nota_venta_venta_id');
    }

    public function devolucionRenta(): BelongsTo
    {
        return $this->belongsTo(DevolucionesRenta::class, 'devolucion_renta_id');
    }

    public function cajaMovimiento(): BelongsTo
    {
        return $this->belongsTo(CajaMovimiento::class, 'caja_movimiento_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
