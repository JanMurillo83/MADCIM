<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'codigo',
        'direccion',
        'telefono',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class, 'sucursal_id');
    }

    public function notasVentaRenta(): HasMany
    {
        return $this->hasMany(NotasVentaRenta::class, 'sucursal_id');
    }

    public function notasVentaVenta(): HasMany
    {
        return $this->hasMany(NotasVentaVenta::class, 'sucursal_id');
    }

    public function facturasCfdi(): HasMany
    {
        return $this->hasMany(FacturasCfdi::class, 'sucursal_id');
    }
}
