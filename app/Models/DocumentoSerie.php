<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentoSerie extends Model
{
    protected $table = 'documento_series';

    protected $fillable = [
        'documento_tipo',
        'serie',
        'descripcion',
        'ultimo_folio',
    ];

    public static function nextFolio(string $documentoTipo, string $serie): int
    {
        return DB::transaction(function () use ($documentoTipo, $serie) {
            $registro = self::query()
                ->where('documento_tipo', $documentoTipo)
                ->where('serie', $serie)
                ->lockForUpdate()
                ->first();

            if (!$registro) {
                throw new \RuntimeException('Serie no configurada para el documento.');
            }

            $registro->ultimo_folio += 1;
            $registro->save();

            return $registro->ultimo_folio;
        });
    }

    public function label(): string
    {
        if ($this->descripcion) {
            return $this->serie . ' - ' . $this->descripcion;
        }

        return $this->serie;
    }
}
