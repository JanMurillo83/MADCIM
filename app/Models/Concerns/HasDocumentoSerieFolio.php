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

            $model->folio = (string) DocumentoSerie::nextFolio($model->getTable(), $model->serie);
        });
    }
}
