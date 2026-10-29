<?php

namespace App\Http\Controllers;

use App\Filament\Resources\NotasVentaRenta\NotasVentaRentaResource;
use App\Filament\Resources\NotasVentaVenta\NotasVentaVentaResource;
use App\Models\Cotizaciones;
use Illuminate\Http\RedirectResponse;

class CotizacionConversionController
{
    public function __invoke(Cotizaciones $cotizacion): RedirectResponse
    {
        abort_unless($cotizacion->estatus === 'Activa', 404);
        $cotizacion->load('partidas');
        $renta = $cotizacion->tipo_cotizacion === 'renta';
        $partidas = $cotizacion->partidas->map(fn ($partida) => [
            'item' => $partida->item,
            'descripcion' => $partida->descripcion,
            'cantidad' => $partida->cantidad,
            'valor_unitario' => $partida->valor_unitario,
            'subtotal' => $partida->subtotal,
            'impuestos' => $partida->impuestos,
            'total' => $partida->total,
            'tipo_nota_renta' => $partida->tipo_nota_renta ?: 'equipo',
            'tipo_renta' => $partida->tipo_renta ?: 'dia',
            'duracion_renta' => $partida->duracion_renta ?: max(1, (int) $cotizacion->dias_renta),
            'dias_renta' => $partida->dias_renta ?: max(1, (int) $cotizacion->dias_renta),
            'metros_m2' => $partida->metros_m2,
            'deposito' => $partida->deposito,
        ])->all();

        session()->flash($renta ? 'cotizacion_partidas_renta' : 'cotizacion_partidas_venta', $partidas);
        session()->flash('cotizacion_origen_id', $cotizacion->id);
        session()->flash('cotizacion_encabezado', [
            'cliente_id' => $cotizacion->cliente_id,
            'sucursal_id' => $cotizacion->sucursal_id,
            'moneda' => $cotizacion->moneda,
            'tipo_cambio' => $cotizacion->tipo_cambio,
            'dias_renta' => max(1, (int) $cotizacion->dias_renta),
        ]);

        return redirect($renta
            ? NotasVentaRentaResource::getUrl('create')
            : NotasVentaVentaResource::getUrl('create'));
    }
}
