<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentoSerieFolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizaciones extends Model
{
    use HasDocumentoSerieFolio;

    protected $fillable = [
        'cliente_id',
        'serie',
        'folio',
        'fecha_emision',
        'dias_renta',
        'fecha_vencimiento',
        'moneda',
        'tipo_cambio',
        'subtotal',
        'impuestos_total',
        'total',
        'estatus',
        'direccion_entrega',
        'observaciones',
        'uso_cfdi',
        'forma_pago',
        'metodo_pago',
        'regimen_fiscal_receptor',
        'rfc_emisor',
        'rfc_receptor',
        'razon_social_receptor',
        'cfdi_uuid',
        'documento_origen_id',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_vencimiento' => 'date',
    ];

    public function partidas(): HasMany
    {
        return $this->hasMany(CotizacionPartidas::class, 'cotizacion_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function documentoOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'documento_origen_id');
    }

    public function documentosRelacionados(): HasMany
    {
        return $this->hasMany(self::class, 'documento_origen_id');
    }
}
