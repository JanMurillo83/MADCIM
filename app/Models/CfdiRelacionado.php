<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CfdiRelacionado extends Model
{
    protected $table = 'cfdi_relacionados';

    protected $fillable = [
        'documento_type',
        'documento_id',
        'tipo_relacion',
        'uuid_relacionado',
        'documento_relacionado_type',
        'documento_relacionado_id',
    ];

    public function documento(): MorphTo
    {
        return $this->morphTo();
    }

    public function documentoRelacionado(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'documento_relacionado_type', 'documento_relacionado_id');
    }
}
