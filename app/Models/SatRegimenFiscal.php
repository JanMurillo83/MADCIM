<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatRegimenFiscal extends Model
{
    protected $table = 'sat_regimen_fiscal';

    protected $primaryKey = 'clave';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'clave',
        'descripcion',
        'aplica_fisica',
        'aplica_moral',
        'vigencia_desde',
        'vigencia_hasta',
    ];

    protected $casts = [
        'aplica_fisica' => 'boolean',
        'aplica_moral' => 'boolean',
        'vigencia_desde' => 'date',
        'vigencia_hasta' => 'date',
    ];
}
