<?php

namespace App\Services;

use App\Models\Productos;
use App\Models\RecepcionCompra;
use App\Models\RecepcionCompraPartida;
use Illuminate\Support\Facades\DB;

class RecepcionCompraInventoryService
{
    public function cerrar(RecepcionCompra $recepcion): void
    {
        if ($recepcion->estatus === 'Cerrada') {
            return;
        }

        if ($recepcion->estatus === 'Cancelada') {
            throw new \RuntimeException('No se puede cerrar una recepcion cancelada.');
        }

        DB::transaction(function () use ($recepcion) {
            $partidas = $recepcion->partidas()->orderBy('id')->get();

            foreach ($partidas as $partida) {
                $this->aplicarPartida($partida);
            }

            $recepcion->update(['estatus' => 'Cerrada']);
        });
    }

    public function cancelar(RecepcionCompra $recepcion): void
    {
        if ($recepcion->estatus === 'Cancelada') {
            return;
        }

        DB::transaction(function () use ($recepcion) {
            if ($recepcion->estatus === 'Cerrada') {
                $partidas = $recepcion->partidas()->orderByDesc('id')->get();

                foreach ($partidas as $partida) {
                    $this->revertirPartida($partida);
                }
            }

            $recepcion->update(['estatus' => 'Cancelada']);
        });
    }

    private function aplicarPartida(RecepcionCompraPartida $partida): void
    {
        $cantidad = (float) $partida->cantidad;

        if (!$partida->producto_id || $cantidad == 0.0) {
            return;
        }

        $producto = Productos::whereKey($partida->producto_id)->lockForUpdate()->first();
        if (!$producto) {
            return;
        }

        $partida->update([
            'existencia_antes' => $producto->existencia,
            'costo_promedio_antes' => $producto->costo,
            'ultimo_costo_antes' => $producto->ultimo_costo,
        ]);

        $existenciaActual = (float) $producto->existencia;
        $costoPromedioActual = (float) $producto->costo;
        $costoNuevo = (float) $partida->precio_unitario;
        $nuevaExistencia = $existenciaActual + $cantidad;
        $nuevoCostoPromedio = $this->calcularCostoPromedio($existenciaActual, $costoPromedioActual, $cantidad, $costoNuevo);

        $producto->update([
            'existencia' => $nuevaExistencia,
            'costo' => $nuevoCostoPromedio,
            'ultimo_costo' => $costoNuevo,
        ]);
    }

    private function revertirPartida(RecepcionCompraPartida $partida): void
    {
        $cantidad = (float) $partida->cantidad;

        if (!$partida->producto_id || $cantidad == 0.0) {
            return;
        }

        $producto = Productos::whereKey($partida->producto_id)->lockForUpdate()->first();
        if (!$producto) {
            return;
        }

        if ($partida->existencia_antes !== null || $partida->costo_promedio_antes !== null || $partida->ultimo_costo_antes !== null) {
            $updates = [];
            if ($partida->existencia_antes !== null) {
                $updates['existencia'] = (float) $partida->existencia_antes;
            }
            if ($partida->costo_promedio_antes !== null) {
                $updates['costo'] = (float) $partida->costo_promedio_antes;
            }
            if ($partida->ultimo_costo_antes !== null) {
                $updates['ultimo_costo'] = (float) $partida->ultimo_costo_antes;
            }

            if (!empty($updates)) {
                $producto->update($updates);
            }

            return;
        }

        $existenciaActual = (float) $producto->existencia;
        $costoPromedioActual = (float) $producto->costo;
        $costoPartida = (float) $partida->precio_unitario;
        $existenciaAnterior = $existenciaActual - $cantidad;
        if ($existenciaAnterior < 0) {
            $existenciaAnterior = 0.0;
        }

        $costoPromedioAnterior = $this->calcularCostoPromedioRevertido(
            $existenciaActual,
            $costoPromedioActual,
            $cantidad,
            $costoPartida,
            $existenciaAnterior
        );

        $producto->update([
            'existencia' => $existenciaAnterior,
            'costo' => $costoPromedioAnterior,
        ]);
    }

    private function calcularCostoPromedio(float $existenciaActual, float $costoPromedioActual, float $cantidad, float $costoNuevo): float
    {
        $nuevaExistencia = $existenciaActual + $cantidad;

        if ($nuevaExistencia <= 0) {
            return 0.0;
        }

        if ($existenciaActual <= 0) {
            return $costoNuevo;
        }

        return (($existenciaActual * $costoPromedioActual) + ($cantidad * $costoNuevo)) / $nuevaExistencia;
    }

    private function calcularCostoPromedioRevertido(
        float $existenciaActual,
        float $costoPromedioActual,
        float $cantidad,
        float $costoPartida,
        float $existenciaAnterior
    ): float {
        if ($existenciaAnterior <= 0) {
            return 0.0;
        }

        $importeActual = $existenciaActual * $costoPromedioActual;
        $importePartida = $cantidad * $costoPartida;
        $importeAnterior = $importeActual - $importePartida;

        if ($importeAnterior <= 0) {
            return 0.0;
        }

        return $importeAnterior / $existenciaAnterior;
    }
}
