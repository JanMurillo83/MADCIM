<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExistenciaSucursal extends Model
{
    protected $table = 'existencias_sucursal';

    protected $fillable = ['producto_id', 'sucursal_id', 'existencia'];

    protected $casts = ['existencia' => 'float'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'producto_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
