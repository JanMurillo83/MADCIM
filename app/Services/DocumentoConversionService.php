<?php

namespace App\Services;

use App\Models\Cotizaciones;
use App\Models\DocumentoSerie;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentoConversionService
{
    /**
     * Convierte una cotización a Nota de Venta (Renta)
     */
    public function cotizacionToNotaVentaRenta(Cotizaciones $cotizacion): NotasVentaRenta
    {
        return DB::transaction(function () use ($cotizacion) {
            // Obtener la serie por defecto para notas de venta renta
            $serieDefault = $this->getDefaultSerie('notas_venta_renta');

            // Crear la nota de venta renta
            $notaVenta = NotasVentaRenta::create([
                'serie' => $serieDefault,
                'folio' => null, // Se asignará automáticamente por el trait
                'fecha_emision' => now(),
                'moneda' => $cotizacion->moneda,
                'tipo_cambio' => $cotizacion->tipo_cambio,
                'subtotal' => $cotizacion->subtotal,
                'impuestos_total' => $cotizacion->impuestos_total,
                'total' => $cotizacion->total,
                'estatus' => 'Activa',
                'uso_cfdi' => $cotizacion->uso_cfdi,
                'forma_pago' => $cotizacion->forma_pago,
                'metodo_pago' => $cotizacion->metodo_pago,
                'regimen_fiscal_receptor' => $cotizacion->regimen_fiscal_receptor,
                'rfc_emisor' => $cotizacion->rfc_emisor,
                'rfc_receptor' => $cotizacion->rfc_receptor,
                'razon_social_receptor' => $cotizacion->razon_social_receptor,
                'documento_origen_id' => $cotizacion->id,
            ]);

            // Copiar las partidas
            foreach ($cotizacion->partidas as $partida) {
                $notaVenta->partidas()->create([
                    'cantidad' => $partida->cantidad,
                    'item' => $partida->item,
                    'descripcion' => $partida->descripcion,
                    'valor_unitario' => $partida->valor_unitario,
                    'subtotal' => $partida->subtotal,
                    'impuestos' => $partida->impuestos,
                    'total' => $partida->total,
                ]);
            }

            return $notaVenta;
        });
    }

    /**
     * Convierte una cotización a Nota de Venta (Venta)
     */
    public function cotizacionToNotaVentaVenta(Cotizaciones $cotizacion): NotasVentaVenta
    {
        return DB::transaction(function () use ($cotizacion) {
            // Obtener la serie por defecto para notas de venta venta
            $serieDefault = $this->getDefaultSerie('notas_venta_venta');

            // Crear la nota de venta venta
            $notaVenta = NotasVentaVenta::create([
                'serie' => $serieDefault,
                'folio' => null, // Se asignará automáticamente por el trait
                'fecha_emision' => now(),
                'moneda' => $cotizacion->moneda,
                'tipo_cambio' => $cotizacion->tipo_cambio,
                'subtotal' => $cotizacion->subtotal,
                'impuestos_total' => $cotizacion->impuestos_total,
                'total' => $cotizacion->total,
                'estatus' => 'Activa',
                'uso_cfdi' => $cotizacion->uso_cfdi,
                'forma_pago' => $cotizacion->forma_pago,
                'metodo_pago' => $cotizacion->metodo_pago,
                'regimen_fiscal_receptor' => $cotizacion->regimen_fiscal_receptor,
                'rfc_emisor' => $cotizacion->rfc_emisor,
                'rfc_receptor' => $cotizacion->rfc_receptor,
                'razon_social_receptor' => $cotizacion->razon_social_receptor,
                'documento_origen_id' => $cotizacion->id,
            ]);

            // Copiar las partidas
            foreach ($cotizacion->partidas as $partida) {
                $notaVenta->partidas()->create([
                    'cantidad' => $partida->cantidad,
                    'item' => $partida->item,
                    'descripcion' => $partida->descripcion,
                    'valor_unitario' => $partida->valor_unitario,
                    'subtotal' => $partida->subtotal,
                    'impuestos' => $partida->impuestos,
                    'total' => $partida->total,
                ]);
            }

            return $notaVenta;
        });
    }

    /**
     * Obtiene la serie por defecto para un tipo de documento
     */
    private function getDefaultSerie(string $documentoTipo): string
    {
        $serie = DocumentoSerie::where('documento_tipo', $documentoTipo)
            ->orderBy('serie')
            ->first();

        if (!$serie) {
            // Crear una serie por defecto si no existe
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
