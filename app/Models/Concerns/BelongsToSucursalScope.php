<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;

trait BelongsToSucursalScope
{
    protected static function bootBelongsToSucursalScope(): void
    {
        static::addGlobalScope('sucursal', function (Builder $builder) {
            $user = auth()->user();
            if (!$user) {
                return;
            }

            if ($user->isAdmin() ?? false) {
                return;
            }

            if (!$user->sucursal_id) {
                $builder->whereRaw('1 = 0');
                return;
            }

            if (in_array('sucursal_id', $builder->getModel()->getFillable(), true)) {
                $builder->where($builder->getModel()->getTable() . '.sucursal_id', $user->sucursal_id);
                return;
            }

            $model = $builder->getModel();
            if (method_exists($model, 'notaVentaRenta') && method_exists($model, 'notaVentaVenta')) {
                $builder->where(function (Builder $query) use ($user): void {
                    $query->whereHas('notaVentaRenta', fn (Builder $nota) => $nota->where('sucursal_id', $user->sucursal_id))
                        ->orWhereHas('notaVentaVenta', fn (Builder $nota) => $nota->where('sucursal_id', $user->sucursal_id));
                });
                return;
            }

            if (method_exists($model, 'notaVentaRenta')) {
                $builder->whereHas('notaVentaRenta', fn (Builder $nota) => $nota->where('sucursal_id', $user->sucursal_id));
                return;
            }

            if (method_exists($model, 'notaEnvio')) {
                $builder->whereHas('notaEnvio', fn (Builder $envio) => $envio->where('sucursal_id', $user->sucursal_id));
                return;
            }

            if (method_exists($model, 'notaOrigen')) {
                $builder->whereHas('notaOrigen', fn (Builder $nota) => $nota->where('sucursal_id', $user->sucursal_id));
                return;
            }

            if (method_exists($model, 'documentoOrigen')) {
                $builder->whereHas('documentoOrigen', fn (Builder $nota) => $nota->where('sucursal_id', $user->sucursal_id));
                return;
            }

            if (method_exists($model, 'partidas')) {
                $builder->whereHas('partidas.notaEnvio', fn (Builder $envio) => $envio->where('sucursal_id', $user->sucursal_id));
                return;
            }

            if (method_exists($model, 'documento')) {
                if ($model->documento() instanceof MorphTo) {
                    $builder->whereHasMorph('documento', [
                        \App\Models\NotasVentaRenta::class,
                        \App\Models\NotasVentaVenta::class,
                        \App\Models\FacturasCfdi::class,
                    ], fn (Builder $documento) => $documento->where('sucursal_id', $user->sucursal_id));
                } else {
                    $builder->whereHas('documento', fn (Builder $documento) => $documento->where('sucursal_id', $user->sucursal_id));
                }
                return;
            }

            if (method_exists($model, 'caja')) {
                $builder->whereHas('caja', fn (Builder $caja) => $caja->where('sucursal_id', $user->sucursal_id));
                return;
            }

            $builder->whereRaw('1 = 0');
        });

        static::creating(function (Model $model) {
            $user = auth()->user();
            if (!$user) {
                return;
            }

            if ($user->isAdmin()) {
                return;
            }

            if (!$user->sucursal_id) {
                throw ValidationException::withMessages([
                    'sucursal_id' => 'Tu usuario debe tener una sucursal asignada para crear documentos.',
                ]);
            }

            $model->validarRelacionesSucursal($model, (int) $user->sucursal_id);

            if (in_array('sucursal_id', $model->getFillable(), true)) {
                $model->sucursal_id = $user->sucursal_id;
            } elseif (method_exists($model, 'caja') && method_exists($model, 'documento')) {
                $tipoDocumento = $model->documento_type;
                $documentoId = $model->documento_id;
                if ($tipoDocumento && $documentoId) {
                    $documento = in_array($tipoDocumento, [
                        \App\Models\NotasVentaRenta::class,
                        \App\Models\NotasVentaVenta::class,
                        \App\Models\FacturasCfdi::class,
                    ], true) ? $tipoDocumento::query()->find($documentoId) : null;
                    if ($documento && (int) $documento->sucursal_id !== (int) $user->sucursal_id) {
                        throw ValidationException::withMessages([
                            'documento_id' => 'No puedes registrar pagos en documentos de otra sucursal.',
                        ]);
                    }
                }
            }

            if (method_exists($model, 'caja') && $model->caja_id) {
                $sucursalCaja = $model->caja()->withoutGlobalScope('sucursal')->value('sucursal_id');
                if ((int) $sucursalCaja !== (int) $user->sucursal_id) {
                    throw ValidationException::withMessages([
                        'caja_id' => 'No puedes registrar movimientos en cajas de otra sucursal.',
                    ]);
                }
            }
        });

        static::updating(function (Model $model): void {
            $user = auth()->user();
            if (!$user || $user->isAdmin()) {
                return;
            }

            if (!$user->sucursal_id) {
                throw ValidationException::withMessages([
                    'sucursal_id' => 'No puedes modificar documentos de otra sucursal.',
                ]);
            }

            if (in_array('sucursal_id', $model->getFillable(), true)) {
                if ((int) $model->getOriginal('sucursal_id') !== (int) $user->sucursal_id) {
                    throw ValidationException::withMessages([
                        'sucursal_id' => 'No puedes modificar documentos de otra sucursal.',
                    ]);
                }

                $model->sucursal_id = $user->sucursal_id;
            } elseif (method_exists($model, 'caja') && method_exists($model, 'documento')) {
                $tipoDocumento = $model->documento_type;
                $documentoId = $model->documento_id;
                $documento = $tipoDocumento && $documentoId && in_array($tipoDocumento, [
                    \App\Models\NotasVentaRenta::class,
                    \App\Models\NotasVentaVenta::class,
                    \App\Models\FacturasCfdi::class,
                ], true) ? $tipoDocumento::query()->find($documentoId) : null;
                if ($documento && (int) $documento->sucursal_id !== (int) $user->sucursal_id) {
                    throw ValidationException::withMessages([
                        'documento_id' => 'No puedes modificar pagos de documentos de otra sucursal.',
                    ]);
                }
            }

            if (method_exists($model, 'caja') && $model->caja_id) {
                $sucursalCaja = $model->caja()->withoutGlobalScope('sucursal')->value('sucursal_id');
                if ((int) $sucursalCaja !== (int) $user->sucursal_id) {
                    throw ValidationException::withMessages([
                        'caja_id' => 'No puedes modificar movimientos de cajas de otra sucursal.',
                    ]);
                }
            }
        });
    }

    protected function validarRelacionesSucursal(Model $model, int $sucursalId): void
    {
        foreach (['notaVentaRenta', 'notaVentaVenta', 'notaEnvio', 'notaOrigen', 'documento'] as $relacion) {
            if (!method_exists($model, $relacion)) {
                continue;
            }

            $relation = $model->{$relacion}();
            if ($relation instanceof MorphTo) {
                continue;
            }
            $foreignKey = $relation->getForeignKeyName();
            if (!$model->getAttribute($foreignKey)) {
                continue;
            }

            $branchId = $relation->withoutGlobalScope('sucursal')->value('sucursal_id');
            if ((int) $branchId !== $sucursalId) {
                throw ValidationException::withMessages([
                    'sucursal_id' => 'El registro relacionado pertenece a otra sucursal o no tiene una sucursal asignada.',
                ]);
            }
        }

        if ($model instanceof \App\Models\Cotizaciones && $model->documento_origen_id) {
            $sucursalOrigen = Cotizaciones::withoutGlobalScope('sucursal')
                ->whereKey($model->documento_origen_id)
                ->value('sucursal_id');
            if ((int) $sucursalOrigen !== $sucursalId) {
                throw ValidationException::withMessages([
                    'documento_origen_id' => 'La cotización de origen pertenece a otra sucursal.',
                ]);
            }
        }

        if ($model instanceof \App\Models\DevolucionesRenta && $model->documento_origen_id) {
            $sucursalOrigen = NotasVentaRenta::withoutGlobalScope('sucursal')
                ->whereKey($model->documento_origen_id)
                ->value('sucursal_id');
            if ((int) $sucursalOrigen !== $sucursalId) {
                throw ValidationException::withMessages([
                    'documento_origen_id' => 'La renta de origen pertenece a otra sucursal.',
                ]);
            }
        }

        if (method_exists($model, 'documento') && $model->documento() instanceof MorphTo && $model->documento_type && $model->documento_id) {
            $type = $model->documento_type;
            $allowedTypes = [
                \App\Models\NotasVentaRenta::class,
                \App\Models\NotasVentaVenta::class,
                \App\Models\FacturasCfdi::class,
            ];

            $documento = in_array($type, $allowedTypes, true)
                ? $type::withoutGlobalScope('sucursal')->find($model->documento_id)
                : null;

            if (!$documento || (int) $documento->sucursal_id !== $sucursalId) {
                throw ValidationException::withMessages([
                    'documento_id' => 'El documento relacionado pertenece a otra sucursal.',
                ]);
            }
        }
    }
}
