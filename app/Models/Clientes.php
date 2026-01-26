<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    protected $fillable = ['clave','nombre','rfc','regimen','codigo','calle','exterior','interior','colonia',
    'municipio','estado','pais','telefono','correo','descuento','lista','contacto','dias_credito','saldo'];
}
