<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CfdiPartidaImpuesto extends Model
{
    protected $table = 'cfdi_partida_impuestos';

    protected $fillable = [
        'partida_type',
        'partida_id',
        'tipo',
        'impuesto',
        'tipo_factor',
        'tasa_o_cuota',
        'base',
        'importe',
    ];

    public function partida(): MorphTo
    {
        return $this->morphTo();
    }
}
