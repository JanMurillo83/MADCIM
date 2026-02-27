<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use App\Models\Concerns\HasDocumentoSerieFolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotasVentaRenta extends Model
{
    use HasDocumentoSerieFolio;
    use BelongsToSucursalScope;

    protected $table = 'notas_venta_renta';
    protected $fillable = [
        'cliente_id',
        'direccion_entrega_id',
        'sucursal_id',
        'user_id',
        'serie',
        'folio',
        'fecha_emision',
        'dias_renta',
        'tipo_renta',
        'condicion_pago',
        'fecha_vencimiento',
        'moneda',
        'tipo_cambio',
        'deposito',
        'subtotal',
        'impuestos_total',
        'total',
        'saldo_pendiente',
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
        'fecha_vencimiento' => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function direccionEntrega(): BelongsTo
    {
        return $this->belongsTo(ClienteDireccionEntrega::class, 'direccion_entrega_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(NotaVentaRentaPartidas::class, 'nota_venta_renta_id');
    }

    public function documentoOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'documento_origen_id');
    }

    public function documentosRelacionados(): HasMany
    {
        return $this->hasMany(self::class, 'documento_origen_id');
    }

    public function devoluciones(): HasMany
    {
        return $this->hasMany(DevolucionesRenta::class, 'documento_origen_id');
    }

    public function registrosRenta(): HasMany
    {
        return $this->hasMany(RegistroRenta::class, 'nota_venta_renta_id');
    }

    public function notasEnvio(): HasMany
    {
        return $this->hasMany(NotaEnvio::class, 'nota_venta_renta_id');
    }
}
