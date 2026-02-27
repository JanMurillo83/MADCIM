<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedores extends Model
{
    protected $fillable = [
        'clave',
        'nombre',
        'rfc',
        'regimen',
        'codigo',
        'calle',
        'exterior',
        'interior',
        'colonia',
        'municipio',
        'estado',
        'pais',
        'telefono',
        'correo',
        'descuento',
        'lista',
        'contacto',
        'dias_credito',
        'saldo',
    ];

    public function requisiciones(): HasMany
    {
        return $this->hasMany(RequisicionCompra::class, 'proveedor_id');
    }

    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'proveedor_id');
    }

    public function recepcionesCompra(): HasMany
    {
        return $this->hasMany(RecepcionCompra::class, 'proveedor_id');
    }
}
