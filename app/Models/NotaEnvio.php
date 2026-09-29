<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\Concerns\BelongsToSucursalScope;

class NotaEnvio extends Model
{
    use BelongsToSucursalScope;

    protected $table = 'notas_envio';

    protected static function booted(): void
    {
        static::saving(function (self $notaEnvio): void {
            $user = auth()->user();
            if ($user && !$user->isAdmin()) {
                if (!$user->sucursal_id) {
                    throw ValidationException::withMessages([
                        'sucursal_id' => 'Tu usuario debe tener una sucursal asignada para crear envíos.',
                    ]);
                }

                if ($notaEnvio->exists && (int) $notaEnvio->getOriginal('sucursal_id') !== (int) $user->sucursal_id) {
                    throw ValidationException::withMessages([
                        'sucursal_id' => 'No puedes modificar envíos de otra sucursal.',
                    ]);
                }

                $notaEnvio->sucursal_id = $user->sucursal_id;
            }

            if (!$notaEnvio->nota_venta_renta_id && !$notaEnvio->nota_venta_venta_id) {
                return;
            }

            if ($notaEnvio->nota_venta_renta_id) {
                $sucursalOrigen = $notaEnvio->notaVentaRenta()->withoutGlobalScope('sucursal')->value('sucursal_id');
            } else {
                $sucursalOrigen = $notaEnvio->notaVentaVenta()->withoutGlobalScope('sucursal')->value('sucursal_id');
            }

            if ($sucursalOrigen && $notaEnvio->sucursal_id && (int) $sucursalOrigen !== (int) $notaEnvio->sucursal_id) {
                throw ValidationException::withMessages([
                    'sucursal_id' => 'El envío debe pertenecer a la misma sucursal que su documento de origen.',
                ]);
            }

            $notaEnvio->sucursal_id ??= $sucursalOrigen;

            if (!$notaEnvio->nota_venta_renta_id || ($notaEnvio->exists && !$notaEnvio->isDirty('inicio_vigencia'))) {
                return;
            }

            $fechaEmision = $notaEnvio->notaVentaRenta()->value('fecha_emision');

            if (blank($notaEnvio->inicio_vigencia) || !$fechaEmision) {
                throw ValidationException::withMessages([
                    'inicio_vigencia' => 'Capture el Inicio de Vigencia y asegúrese de que la nota origen tenga fecha de emisión.',
                ]);
            }

            $fechaEmision = Carbon::parse($fechaEmision)->startOfDay();
            $inicioVigencia = Carbon::parse($notaEnvio->inicio_vigencia)->startOfDay();

            if ($inicioVigencia->lt($fechaEmision) || $inicioVigencia->gt($fechaEmision->copy()->addDays(4))) {
                throw ValidationException::withMessages([
                    'inicio_vigencia' => 'El Inicio de Vigencia debe estar entre la fecha de emisión de la nota origen y los 4 días posteriores.',
                ]);
            }
        });
    }

    protected $fillable = [
        'sucursal_id',
        'serie',
        'folio',
        'nota_venta_renta_id',
        'nota_venta_venta_id',
        'cliente_id',
        'direccion_entrega_id',
        'fecha_emision',
        'inicio_vigencia',
        'dias_renta',
        'fecha_vencimiento',
        'observaciones',
        'estatus',
        'estado_renta',
        'user_id',
    ];

    protected $casts = [
        'sucursal_id' => 'integer',
        'fecha_emision' => 'date',
        'inicio_vigencia' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function notaVentaRenta(): BelongsTo
    {
        return $this->belongsTo(NotasVentaRenta::class, 'nota_venta_renta_id');
    }

    public function notaVentaVenta(): BelongsTo
    {
        return $this->belongsTo(NotasVentaVenta::class, 'nota_venta_venta_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function direccionEntrega(): BelongsTo
    {
        return $this->belongsTo(ClienteDireccionEntrega::class, 'direccion_entrega_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(NotaEnvioPartida::class, 'nota_envio_id');
    }
}
