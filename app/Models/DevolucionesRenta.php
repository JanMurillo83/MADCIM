<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use App\Models\Concerns\HasDocumentoSerieFolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DevolucionesRenta extends Model
{
    use HasDocumentoSerieFolio;
    use BelongsToSucursalScope;

    protected $table = 'devoluciones_renta';
    protected $fillable = [
        'sucursal_id',
        'serie',
        'folio',
        'folio_interno',
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

    protected $casts = [
        'fecha_emision' => 'datetime',
        'cfdi_fecha_timbrado' => 'datetime',
        'cfdi_fecha_cancelacion' => 'datetime',
    ];

    public function notaOrigen(): BelongsTo
    {
        return $this->belongsTo(NotasVentaRenta::class, 'documento_origen_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $devolucion): void {
            $user = auth()->user();
            if (!$user) {
                return;
            }

            $sucursalOrigen = $devolucion->documento_origen_id
                ? NotasVentaRenta::withoutGlobalScope('sucursal')->whereKey($devolucion->documento_origen_id)->value('sucursal_id')
                : null;

            if ($sucursalOrigen) {
                $devolucion->sucursal_id = $sucursalOrigen;
                return;
            }

            if (!$user->isAdmin()) {
                if (!$user->sucursal_id) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'sucursal_id' => 'Tu usuario debe tener una sucursal asignada para crear devoluciones.',
                    ]);
                }

                $devolucion->sucursal_id = $user->sucursal_id;
            }

            if ($devolucion->documento_origen_id && !$sucursalOrigen) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'documento_origen_id' => 'No se encontró la sucursal del documento origen de la devolución.',
                ]);
            }
        });
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(DevolucionRentaPartidas::class, 'devolucion_renta_id');
    }

    public function documentoOrigen(): BelongsTo
    {
        return $this->belongsTo(NotasVentaRenta::class, 'documento_origen_id');
    }

    public function cfdiRelacionados(): MorphMany
    {
        return $this->morphMany(CfdiRelacionado::class, 'documento');
    }
}
