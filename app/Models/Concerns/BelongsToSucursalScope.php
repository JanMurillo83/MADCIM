<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

            $builder->where($builder->getModel()->getTable() . '.sucursal_id', $user->sucursal_id);
        });

        static::creating(function (Model $model) {
            $user = auth()->user();
            if (!$user || ($user->isAdmin() ?? false)) {
                return;
            }

            if (property_exists($model, 'fillable') && in_array('sucursal_id', $model->getFillable(), true)) {
                $model->sucursal_id = $user->sucursal_id;
            }
        });
    }
}
