<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentoSerieFolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevolucionesVenta extends Model
{
    use HasDocumentoSerieFolio;

    protected $table = 'devoluciones_venta';
    protected $fillable = [
        'serie',
        'folio',
        'fecha_emision',
        'moneda',
        'tipo_cambio',
        'subtotal',
        'impuestos_total',
        'total',
        'estatus',
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
    ];

    public function partidas(): HasMany
    {
        return $this->hasMany(DevolucionVentaPartidas::class, 'devolucion_venta_id');
    }

    public function documentoOrigen(): BelongsTo
    {
        return $this->belongsTo(NotasVentaVenta::class, 'documento_origen_id');
    }
}
