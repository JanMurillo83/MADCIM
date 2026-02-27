<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursalScope;
use App\Models\Concerns\HasDocumentoSerieFolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCompra extends Model
{
    use HasDocumentoSerieFolio;
    use BelongsToSucursalScope;

    protected $table = 'ordenes_compra';

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
        'proveedor_id',
        'requisicion_compra_id',
        'sucursal_id',
        'user_id',
        'observaciones',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedores::class, 'proveedor_id');
    }

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(RequisicionCompra::class, 'requisicion_compra_id');
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
        return $this->hasMany(OrdenCompraPartida::class, 'orden_compra_id');
    }

    public function recepciones(): HasMany
    {
        return $this->hasMany(RecepcionCompra::class, 'orden_compra_id');
    }
}
