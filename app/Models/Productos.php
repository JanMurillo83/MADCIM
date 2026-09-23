<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Productos extends Model
{
    protected $fillable = ['clave', 'clave_prod_serv', 'clave_unidad', 'unidad_sat', 'objeto_imp', 'impuesto', 'tipo_factor',
    'tasa_o_cuota', 'descripcion','m2_cubre','costo','ultimo_costo','precio_venta','precio_renta_mes',
    'precio_renta_dia','precio_renta_semana','existencia','grupo','linea','largo','ancho','imagen','producto_base_id'];

    public function productoBase(): BelongsTo
    {
        return $this->belongsTo(self::class, 'producto_base_id');
    }

    public function pedaceria(): HasMany
    {
        return $this->hasMany(self::class, 'producto_base_id');
    }
}
