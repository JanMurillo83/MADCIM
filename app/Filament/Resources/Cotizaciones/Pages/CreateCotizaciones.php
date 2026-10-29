<?php

namespace App\Filament\Resources\Cotizaciones\Pages;

use App\Filament\Resources\Cotizaciones\CotizacionesResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Productos;
use App\Support\Impuestos;
use App\Enums\TipoNotaRenta;
use App\Services\RentaMaderaM2Service;

class CreateCotizaciones extends CreateRecord
{
    protected static string $resource = CotizacionesResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tipo = ($data['tipo_cotizacion'] ?? 'venta') === 'renta' ? 'renta' : 'venta';
        $subtotal = $iva = $deposito = 0.0;
        $partidas = $this->form->getRawState()['partidas'] ?? $data['partidas'] ?? [];

        foreach ($partidas as &$partida) {
            $tipoRenta = TipoNotaRenta::tryFrom($partida['tipo_nota_renta'] ?? 'equipo') ?? TipoNotaRenta::Equipo;
            $productoId = $partida['item'] ?? null;
            if ($tipo === 'renta' && $tipoRenta->esMaderaM2()) {
                $productoId = RentaMaderaM2Service::productoRentaM2Id($tipoRenta);
            }
            $producto = Productos::find($productoId);
            if (!$producto) {
                continue;
            }

            $cantidad = max(0.0, (float) ($partida['cantidad'] ?? 0));
            if ($tipo === 'venta') {
                $total = round($cantidad * (float) $producto->precio_venta, 2);
                $desglose = Impuestos::desglosarIvaIncluido($total);
                $partida = array_merge($partida, ['descripcion' => $producto->descripcion, 'valor_unitario' => $producto->precio_venta, 'subtotal' => $desglose['subtotal'], 'impuestos' => $desglose['iva'], 'total' => $total, 'deposito' => 0]);
            } elseif ($tipoRenta->esMaderaM2()) {
                $metros = max(0.0, (float) ($partida['metros_m2'] ?? 0));
                $calculo = RentaMaderaM2Service::calcular($tipoRenta, $metros);
                $partida = array_merge($partida, ['item' => $producto->id, 'descripcion' => $producto->descripcion . ' - ' . $metros . ' M2', 'cantidad' => 1, 'valor_unitario' => $calculo['precio_renta_m2'], 'subtotal' => $calculo['subtotal_renta'], 'impuestos' => $calculo['iva_renta'], 'total' => $calculo['total_renta'], 'deposito' => $calculo['deposito'], 'tipo_nota_renta' => $tipoRenta->value]);
            } else {
                $tipoRentaBase = $partida['tipo_renta'] ?? 'dia';
                $duracion = max(1, (int) ($partida['duracion_renta'] ?? $data['dias_renta'] ?? 1));
                $precio = $tipoRenta->esMadera() ? (float) $producto->precio_renta_dia : (float) match ($tipoRentaBase) { 'semana' => $producto->precio_renta_semana, 'mes' => $producto->precio_renta_mes, default => $producto->precio_renta_dia };
                $cantidad = $tipoRenta->esEquipo() ? 1 : $cantidad;
                $dias = $tipoRenta->esMadera() ? min(30, max(1, (int) ($partida['dias_renta'] ?? $data['dias_renta'] ?? 1))) : ($tipoRentaBase === 'semana' ? $duracion * 7 : ($tipoRentaBase === 'mes' ? $duracion * 30 : $duracion));
                $total = round($cantidad * $precio * ($tipoRenta->esEquipo() ? $duracion : 1), 2);
                $desglose = Impuestos::desglosarIvaIncluido($total);
                $partida = array_merge($partida, ['descripcion' => $producto->descripcion, 'cantidad' => $cantidad, 'valor_unitario' => $precio, 'subtotal' => $desglose['subtotal'], 'impuestos' => $desglose['iva'], 'total' => $total, 'deposito' => $tipoRenta->esMadera() ? round($total * 0.5, 2) : 0, 'tipo_nota_renta' => $tipoRenta->value, 'tipo_renta' => $tipoRentaBase, 'duracion_renta' => $duracion, 'dias_renta' => $dias]);
            }
            $subtotal += (float) $partida['subtotal'];
            $iva += (float) $partida['impuestos'];
            $deposito += (float) ($partida['deposito'] ?? 0);
        }
        unset($partida);

        $data['tipo_cotizacion'] = $tipo;
        $data['partidas'] = $partidas;
        $data['subtotal'] = round($subtotal, 2);
        $data['impuestos_total'] = round($iva, 2);
        $data['deposito'] = round($deposito, 2);
        $data['total'] = round($subtotal + $iva + $deposito, 2);
        return $data;
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
