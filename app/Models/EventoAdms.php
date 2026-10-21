<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoAdms extends Model
{
    protected $table = 'eventos_adms';

    protected $fillable = [
        'dispositivo_biometrico_id',
        'numero_serie',
        'huella_evento',
        'identificador',
        'registrada_en',
        'tipo_operacion',
        'estado',
        'checada_id',
        'payload_original',
        'error',
        'recibida_en',
        'procesada_en',
    ];

    protected function casts(): array
    {
        return [
            'registrada_en' => 'datetime',
            'recibida_en' => 'datetime',
            'procesada_en' => 'datetime',
        ];
    }

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(DispositivoBiometrico::class, 'dispositivo_biometrico_id');
    }

    public function checada(): BelongsTo
    {
        return $this->belongsTo(Checada::class);
    }
}
