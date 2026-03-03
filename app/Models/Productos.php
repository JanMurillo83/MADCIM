<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Productos extends Model
{
    protected $fillable = ['clave', 'clave_prod_serv', 'clave_unidad', 'unidad_sat', 'objeto_imp', 'impuesto', 'tipo_factor',
    'tasa_o_cuota', 'descripcion','m2_cubre','costo','ultimo_costo','precio_venta','precio_renta_mes',
    'precio_renta_dia','precio_renta_semana','existencia','grupo','linea','largo','ancho','imagen'];
}
