<?php

namespace App\Services;

use App\Models\DocumentoSerie;
use App\Models\OrdenCompra;
use App\Models\RecepcionCompra;
use App\Models\RequisicionCompra;
use Illuminate\Support\Facades\DB;

class ComprasConversionService
{
    public function requisicionToOrdenCompra(RequisicionCompra $requisicion): OrdenCompra
    {
        return DB::transaction(function () use ($requisicion) {
            $serieDefault = $this->getDefaultSerie('ordenes_compra');

            $orden = OrdenCompra::create([
                'serie' => $serieDefault,
                'folio' => null,
                'proveedor_id' => $requisicion->proveedor_id,
                'requisicion_compra_id' => $requisicion->id,
                'sucursal_id' => $requisicion->sucursal_id,
                'user_id' => auth()->id(),
                'fecha_emision' => now(),
                'moneda' => $requisicion->moneda,
                'tipo_cambio' => $requisicion->tipo_cambio,
                'subtotal' => $requisicion->subtotal,
                'impuestos_total' => $requisicion->impuestos_total,
                'total' => $requisicion->total,
                'estatus' => 'Nueva',
                'observaciones' => $requisicion->observaciones,
            ]);

            foreach ($requisicion->partidas as $partida) {
                $orden->partidas()->create([
                    'producto_id' => $partida->producto_id,
                    'descripcion' => $partida->descripcion,
                    'cantidad' => $partida->cantidad,
                    'precio_unitario' => $partida->precio_unitario,
                    'subtotal' => $partida->subtotal,
                    'impuestos' => $partida->impuestos,
                    'total' => $partida->total,
                ]);
            }

            $requisicion->update(['estatus' => 'Enlazada']);

            return $orden;
        });
    }

    public function ordenToRecepcionCompra(OrdenCompra $orden): RecepcionCompra
    {
        return DB::transaction(function () use ($orden) {
            $serieDefault = $this->getDefaultSerie('recepciones_compra');

            $recepcion = RecepcionCompra::create([
                'serie' => $serieDefault,
                'folio' => null,
                'proveedor_id' => $orden->proveedor_id,
                'orden_compra_id' => $orden->id,
                'sucursal_id' => $orden->sucursal_id,
                'user_id' => auth()->id(),
                'fecha_emision' => now(),
                'moneda' => $orden->moneda,
                'tipo_cambio' => $orden->tipo_cambio,
                'subtotal' => $orden->subtotal,
                'impuestos_total' => $orden->impuestos_total,
                'total' => $orden->total,
                'estatus' => 'Nueva',
                'observaciones' => $orden->observaciones,
            ]);

            foreach ($orden->partidas as $partida) {
                $recepcion->partidas()->create([
                    'producto_id' => $partida->producto_id,
                    'descripcion' => $partida->descripcion,
                    'cantidad' => $partida->cantidad,
                    'precio_unitario' => $partida->precio_unitario,
                    'subtotal' => $partida->subtotal,
                    'impuestos' => $partida->impuestos,
                    'total' => $partida->total,
                ]);
            }

            $orden->update(['estatus' => 'Enlazada']);

            return $recepcion;
        });
    }

    private function getDefaultSerie(string $documentoTipo): string
    {
        $serie = DocumentoSerie::where('documento_tipo', $documentoTipo)
            ->orderBy('serie')
            ->first();

        if (!$serie) {
            $serie = DocumentoSerie::create([
                'documento_tipo' => $documentoTipo,
                'serie' => 'A',
                'descripcion' => 'Serie por defecto',
                'ultimo_folio' => 0,
            ]);
        }

        return $serie->serie;
    }
}
