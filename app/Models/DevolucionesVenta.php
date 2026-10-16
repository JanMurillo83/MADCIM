<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentoSerieFolio;
use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DevolucionesVenta extends Model
{
    use HasDocumentoSerieFolio;
    use BelongsToSucursalScope;

    protected $table = 'devoluciones_venta';
    protected $fillable = [
        'sucursal_id',
        'serie',
        'folio',
        'condiciones_pago',
        'fecha_emision',
        'moneda',
        'tipo_cambio',
        'subtotal',
        'descuento',
        'impuestos_total',
        'total',
        'tipo_comprobante',
        'exportacion',
        'lugar_expedicion',
        'estatus',
        'uso_cfdi',
        'forma_pago',
        'metodo_pago',
        'regimen_fiscal_emisor',
        'regimen_fiscal_receptor',
        'rfc_emisor',
        'nombre_emisor',
        'rfc_receptor',
        'razon_social_receptor',
        'domicilio_fiscal_receptor',
        'cfdi_uuid',
        'cfdi_version',
        'cfdi_xml',
        'cfdi_pdf',
        'cfdi_no_certificado',
        'cfdi_certificado',
        'cfdi_sello',
        'cfdi_cadena_original',
        'cfdi_fecha_timbrado',
        'cfdi_fecha_cancelacion',
        'cfdi_motivo_cancelacion',
        'cfdi_folio_sustitucion',
        'cfdi_estatus_sat',
        'cfdi_es_cancelable',
        'cfdi_estatus_cancelacion',
        'documento_origen_id',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    protected $casts = [
        'fecha_emision' => 'datetime',
        'cfdi_fecha_timbrado' => 'datetime',
        'cfdi_fecha_cancelacion' => 'datetime',
    ];

    public function sucursalParaFolio(): ?int
    {
        $sucursal = $this->documento_origen_id
            ? NotasVentaVenta::withoutGlobalScope('sucursal')->whereKey($this->documento_origen_id)->value('sucursal_id')
            : null;

        return $sucursal ? (int) $sucursal : (auth()->user()?->sucursal_id ? (int) auth()->user()->sucursal_id : null);
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(DevolucionVentaPartidas::class, 'devolucion_venta_id');
    }

    public function documentoOrigen(): BelongsTo
    {
        return $this->belongsTo(NotasVentaVenta::class, 'documento_origen_id');
    }

    public function cfdiRelacionados(): MorphMany
    {
        return $this->morphMany(CfdiRelacionado::class, 'documento');
    }
}
