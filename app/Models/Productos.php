<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Productos extends Model
{
    protected $fillable = ['clave', 'descripcion','m2_cubre','costo','precio_venta','precio_renta_mes',
    'precio_renta_dia','precio_renta_semana','existencia','grupo','linea','largo','ancho','imagen'];
}
