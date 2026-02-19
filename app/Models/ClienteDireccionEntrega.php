<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteDireccionEntrega extends Model
{
    protected $table = 'cliente_direcciones_entrega';

    protected $fillable = [
        'cliente_id',
        'nombre_direccion',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'municipio',
        'estado',
        'codigo_postal',
        'pais',
        'referencias',
        'contacto_nombre',
        'contacto_telefono',
        'es_principal',
        'activa',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'activa' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function getDireccionCompletaAttribute(): string
    {
        $direccion = "{$this->calle} {$this->numero_exterior}";
        if ($this->numero_interior) {
            $direccion .= " Int. {$this->numero_interior}";
        }
        $direccion .= ", {$this->colonia}, {$this->municipio}, {$this->estado}, CP {$this->codigo_postal}";
        return $direccion;
    }
}
