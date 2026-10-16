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
        'sucursal_id',
        'descripcion',
        'ultimo_folio',
    ];

    public static function nextFolio(string $documentoTipo, string $serie, ?int $sucursalId = null): int
    {
        return DB::transaction(function () use ($documentoTipo, $serie, $sucursalId) {
            $registro = self::query()
                ->where('documento_tipo', $documentoTipo)
                ->where('serie', $serie)
                ->where('sucursal_id', $sucursalId)
                ->lockForUpdate()
                ->first();

            if (!$registro) {
                $registro = self::query()
                    ->where('documento_tipo', $documentoTipo)
                    ->where('serie', $serie)
                    ->whereNull('sucursal_id')
                    ->lockForUpdate()
                    ->first();

                if (!$registro) {
                    throw new \RuntimeException('Serie no configurada para el documento.');
                }

                $registroSucursal = self::query()
                    ->where('documento_tipo', $documentoTipo)
                    ->where('serie', $serie)
                    ->where('sucursal_id', $sucursalId)
                    ->lockForUpdate()
                    ->first();

                if ($registroSucursal) {
                    $registro = $registroSucursal;
                } else {
                    $maximo = DB::table($documentoTipo)
                        ->where('serie', $serie)
                        ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
                        ->max(DB::raw('CAST(folio AS UNSIGNED)')) ?? 0;

                    $registro = self::query()->create([
                        'documento_tipo' => $documentoTipo,
                        'serie' => $serie,
                        'sucursal_id' => $sucursalId,
                        'descripcion' => $registro->descripcion,
                        'ultimo_folio' => $maximo,
                    ]);
                }
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
