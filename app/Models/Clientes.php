<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clientes extends Model
{
    protected $fillable = ['clave','nombre','rfc','regimen','codigo','calle','exterior','interior','colonia',
    'municipio','estado','pais','telefono','correo','descuento','lista','contacto','dias_credito','saldo'];

    public function direccionesEntrega(): HasMany
    {
        return $this->hasMany(ClienteDireccionEntrega::class, 'cliente_id');
    }

    public function direccionesEntregaActivas(): HasMany
    {
        return $this->hasMany(ClienteDireccionEntrega::class, 'cliente_id')
            ->where('activa', true);
    }

    public function notasVentaRenta(): HasMany
    {
        return $this->hasMany(NotasVentaRenta::class, 'cliente_id');
    }
}
