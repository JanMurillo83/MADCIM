<?php

namespace App\Models\Concerns;

use App\Models\DocumentoSerie;
use Illuminate\Database\Eloquent\Model;

trait HasDocumentoSerieFolio
{
    protected static function bootHasDocumentoSerieFolio(): void
    {
        static::creating(function (Model $model) {
            if (!empty($model->folio) || empty($model->serie)) {
                return;
            }

            if (empty($model->sucursal_id)) {
                $model->sucursal_id = method_exists($model, 'sucursalParaFolio')
                    ? $model->sucursalParaFolio()
                    : auth()->user()?->sucursal_id;
            }

            $model->folio = (string) DocumentoSerie::nextFolio(
                $model->getTable(),
                $model->serie,
                $model->sucursal_id ? (int) $model->sucursal_id : null,
            );
        });
    }
}
