<?php

namespace App\Services;

use App\Enums\TipoNotaRenta;
use App\Models\NotaEnvioPartida;
use App\Models\NotaVentaVentaPartidas;
use App\Models\NotaVentaRentaPartidas;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Productos;
use App\Models\RegistroRenta;
use App\Models\Configuracion;
use App\Models\DocumentoSerie;
use App\Support\Impuestos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AmpliacionVigenciaRentaService
{
    /**
     * @param array<int, array{partida_id?: int, fila_m2_id?: int, cantidad?: float, metros_m2?: float, tarifa?: string}> $partidas
     */
    public function ampliar(NotasVentaRenta $nota, array $partidas, ?int $userId = null): NotasVentaVenta
    {
        return DB::transaction(function () use ($nota, $partidas, $userId): NotasVentaVenta {
            $nota = NotasVentaRenta::query()
                ->with(['partidas.producto', 'desgloseM2.producto'])
                ->lockForUpdate()
                ->findOrFail($nota->id);

            if ($nota->estatus !== 'Activa') {
                throw ValidationException::withMessages([
                    'partidas' => 'Solo se puede ampliar la vigencia de una Nota de Venta Renta activa.',
                ]);
            }

            $nota->loadMissing(['cliente']);

            $lineas = [];
            $nuevoVencimiento = null;
            $equipoAmpliado = false;

            foreach ($partidas as $captura) {
                $filaM2Id = (int) ($captura['fila_m2_id'] ?? 0);
                $filaM2 = $filaM2Id ? $nota->desgloseM2->firstWhere('id', $filaM2Id) : null;
                $partidaId = (int) ($captura['partida_id'] ?? $filaM2?->nota_venta_renta_partida_id ?? 0);
                $partida = $nota->partidas->firstWhere('id', $partidaId);
                if (!$partida) {
                    continue;
                }

                $tipo = TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? $nota->tipo_nota_renta ?? 'equipo')
                    ?? TipoNotaRenta::Equipo;
                if ($tipo->esEquipo() && empty($captura['dias_ampliacion'])) {
                    continue;
                }
                if (!$tipo->esEquipo() && empty($captura['cantidad']) && empty($captura['metros_m2'])) {
                    continue;
                }

                if ($tipo->esMaderaM2()) {
                    $filaM2 ??= $nota->desgloseM2->firstWhere('nota_venta_renta_partida_id', $partida->id);
                }

                if (!$tipo->esEquipo() && $tipo->esMaderaM2() && !$filaM2) {
                    $filaM2 = $partida;
                }
                $dias = max(1, (int) ($captura['dias_ampliacion'] ?? $partida->dias_renta ?? $nota->dias_renta ?? 1));
                $vigenciaPartida = $partida->fecha_vencimiento?->copy()
                    ?? $nota->fecha_vencimiento?->copy()
                    ?? now()->startOfDay();

                if ($tipo->esEquipo()) {
                    $equipoAmpliado = true;
                    $registros = RegistroRenta::query()
                        ->where('nota_venta_renta_id', $nota->id)
                        ->whereHas('notaEnvioPartida', fn ($query) => $query->where('nota_venta_renta_partida_id', $partida->id))
                        ->where('estado', 'Activo')
                        ->whereNotNull('fecha_vencimiento')
                        ->whereRaw('cantidad > COALESCE(cantidad_devuelta, 0)')
                        ->get();
                    if ($registros->isEmpty()) {
                        continue;
                    }
                    $diasAmpliacion = max(1, (int) ($captura['dias_ampliacion'] ?? 1));
                    $registros = $registros->filter(fn (RegistroRenta $registro): bool =>
                        (float) $registro->cantidad > (float) ($registro->cantidad_devuelta ?? 0)
                        && $registro->fecha_vencimiento
                    );
                    if ($registros->isEmpty()) {
                        continue;
                    }

                    $precio = match ($partida->tipo_renta ?? $nota->tipo_renta ?? 'dia') {
                        'semana' => (float) ($partida->producto?->precio_renta_semana ?? 0),
                        'mes' => (float) ($partida->producto?->precio_renta_mes ?? 0),
                        default => (float) ($partida->producto?->precio_renta_dia ?? 0),
                    };
                    $cantidadPorRegistro = $registros->mapWithKeys(fn (RegistroRenta $registro): array => [
                        $registro->id => (float) $registro->cantidad - (float) ($registro->cantidad_devuelta ?? 0),
                    ]);
                    $cantidad = (float) $cantidadPorRegistro->sum();
                    if ($cantidad <= 0) {
                        continue;
                    }
                    $diasVigentesOriginales = max(1, (int) ($partida->dias_renta ?? $nota->dias_renta ?? 1));
                    $factorDias = $diasAmpliacion / $diasVigentesOriginales;
                    $total = round($precio * max(1, (int) ($partida->duracion_renta ?? 1)) * $cantidad * $factorDias, 2);
                    $lineas[] = $this->crearLinea(
                        $partida->item,
                        'Renovación de Renta - ' . ($partida->descripcion ?: $partida->producto?->descripcion),
                        $cantidad,
                        $total,
                    );

                    $venceNuevoPartida = null;
                    foreach ($registros as $registro) {
                        $cantidadRegistro = (float) ($cantidadPorRegistro->get($registro->id) ?? 0);
                        if ($cantidadRegistro <= 0) {
                            continue;
                        }
                        $venceActual = $registro->fecha_vencimiento?->copy() ?? $vigenciaPartida;
                        $nuevoVence = $venceActual->copy()->addDays($diasAmpliacion);
                        $registro->fecha_vencimiento = $nuevoVence;
                        $registro->dias_renta = (int) ($registro->dias_renta ?? 0) + $diasAmpliacion;
                        $registro->save();
                        $venceNuevoPartida = $this->maxFecha($venceNuevoPartida, $nuevoVence);

                        if ($registro->nota_envio_partida_id) {
                            NotaEnvioPartida::query()->whereKey($registro->nota_envio_partida_id)
                                ->update(['fecha_vencimiento' => $nuevoVence->toDateString()]);
                        }
                    }
                    if ($venceNuevoPartida) {
                        $nuevoVencimiento = $this->maxFecha($nuevoVencimiento, $venceNuevoPartida);
                        $partida->fecha_vencimiento = $venceNuevoPartida->toDateString();
                        $partida->save();
                    }

                    continue;
                }

                $tarifa = $this->resolverTarifa($captura['tarifa'] ?? null);
                if ($tipo->esMaderaM2()) {
                    $m2 = (float) ($captura['metros_m2'] ?? 0);
                    if ($m2 <= 0) {
                        continue;
                    }
                    if ($m2 > (float) ($filaM2 instanceof NotaVentaRentaPartidas ? $filaM2->metros_m2 : $filaM2?->m2_total)) {
                        throw ValidationException::withMessages([
                            'partidas' => 'Los M² a renovar superan el metraje registrado en la Nota de Venta Renta.',
                        ]);
                    }

                    if (!$filaM2 instanceof NotaVentaRentaPartidas && !$filaM2 instanceof \App\Models\NotaVentaRentaM2Desglose) {
                        throw ValidationException::withMessages([
                            'partidas' => 'No se encontró la partida de madera por M² para esta ampliación.',
                        ]);
                    }
                    if (!$tipo->esEquipo() && !$tipo->esMaderaM2()) {
                        continue;
                    }

                    $productoId = match ($tipo) {
                        TipoNotaRenta::MaderaM2Triplay15 => 146,
                        TipoNotaRenta::MaderaM2Triplay18 => 145,
                        default => 143,
                    };
                    $producto = Productos::find($productoId);
                    $configuracion = Configuracion::first();
                    $precioM2 = match ($tipo) {
                        TipoNotaRenta::MaderaM2Triplay15 => (float) ($configuracion?->imp_triqui_met ?? 0),
                        TipoNotaRenta::MaderaM2Triplay18 => (float) ($configuracion?->imp_tridie_met ?? 0),
                        default => (float) ($configuracion?->imp_tabla_met ?? 0),
                    };
                    // La tarifa configurada para madera por M² ya corresponde al periodo de renta.
                    $total = round($m2 * $precioM2 * $tarifa, 2);
                    $lineas[] = $this->crearLinea(
                        (string) ($producto?->id ?? $partida->item),
                        'Renovación de Renta - ' . ($producto?->descripcion ?? $partida->descripcion) . " ({$m2} M2)",
                        $m2,
                        $total,
                    );
                    continue;
                }

                $cantidad = (float) ($captura['cantidad'] ?? 0);
                if ($cantidad <= 0) {
                    continue;
                }

                $registrosMadera = RegistroRenta::query()
                    ->where('nota_venta_renta_id', $nota->id)
                    ->where('producto_id', $partida->item)
                    ->where('estado', 'Activo')
                    ->get();
                $registrosMadera = $registrosMadera->filter(fn (RegistroRenta $registro): bool =>
                    (float) $registro->cantidad > (float) ($registro->cantidad_devuelta ?? 0)
                    && $registro->fecha_vencimiento
                );
                $cantidadActiva = $registrosMadera->sum(fn (RegistroRenta $registro): float => (float) $registro->cantidad - (float) ($registro->cantidad_devuelta ?? 0));
                if ($cantidad > $cantidadActiva) {
                    throw ValidationException::withMessages([
                        'partidas' => 'La cantidad a renovar supera las piezas que siguen activas en renta.',
                    ]);
                }

                if (!$tipo->esMaderaPieza()) {
                    continue;
                }
                $precio = (float) ($partida->producto?->precio_renta_dia ?? 0);
                $total = round($cantidad * $precio * $dias * $tarifa, 2);
                $lineas[] = $this->crearLinea(
                    (string) $partida->item,
                    'Renovación de Renta - ' . ($partida->descripcion ?: $partida->producto?->descripcion),
                    $cantidad,
                    $total,
                );
            }

            if ($nuevoVencimiento && $equipoAmpliado) {
                $nota->fecha_vencimiento = $nuevoVencimiento;
                $nota->dias_renta = max(1, (int) $nota->fecha_emision->diffInDays($nuevoVencimiento, true));
                $nota->save();
            }

            if ($lineas === []) {
                throw ValidationException::withMessages([
                    'partidas' => 'Capture una cantidad o metraje mayor a cero para generar la renovación.',
                ]);
            }

            $subtotal = round(array_sum(array_column($lineas, 'subtotal')), 2);
            $impuestos = round(array_sum(array_column($lineas, 'impuestos')), 2);
            $total = round($subtotal + $impuestos, 2);

            $notaVenta = (new NotasVentaVenta([
                'cliente_id' => $nota->cliente_id,
                'sucursal_id' => $nota->sucursal_id,
                'user_id' => $userId ?? Auth::id(),
                'serie' => $this->resolverSerieVenta(),
                'fecha_emision' => now(),
                'condicion_pago' => 'credito',
                'fecha_vencimiento_pago' => now()->addDays(max(1, (int) ($nota->cliente?->dias_credito ?? 1)))->toDateString(),
                'moneda' => $nota->moneda ?? 'MXN',
                'tipo_cambio' => $nota->tipo_cambio ?? 1,
                'subtotal' => $subtotal,
                'impuestos_total' => $impuestos,
                'total' => $total,
                'saldo_pendiente' => $total,
                'estatus' => 'Activa',
                'estatus_envio' => 'Entregada',
                'forma_pago' => '99',
                'metodo_pago' => 'PUE',
                'documento_origen_id' => $nota->id,
            ]))->omitirValidacionEstatusCliente();
            $notaVenta->save();

            foreach ($lineas as $linea) {
                NotaVentaVentaPartidas::create([
                    'nota_venta_venta_id' => $notaVenta->id,
                    ...$linea,
                ]);
            }

            return $notaVenta;
        });
    }

    private function crearLinea(string $productoId, string $descripcion, float $cantidad, float $totalConIva): array
    {
        $impuestos = Impuestos::desglosarIvaIncluido($totalConIva);

        return [
            'item' => $productoId,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'valor_unitario' => $cantidad > 0 ? round($totalConIva / $cantidad, 2) : 0,
            'subtotal' => $impuestos['subtotal'],
            'impuestos' => $impuestos['iva'],
            'total' => round($totalConIva, 2),
        ];
    }

    private function resolverTarifa(?string $tarifa): float
    {
        return match ($tarifa) {
            '0' => 0.0,
            '50' => 0.5,
            '100' => 1.0,
            default => throw ValidationException::withMessages([
                'partidas' => 'Seleccione una tarifa válida para cada partida de madera.',
            ]),
        };
    }

    private function resolverSerieVenta(): string
    {
        $serie = DocumentoSerie::query()
            ->where('documento_tipo', 'notas_venta_venta')
            ->orderBy('id')
            ->value('serie');

        if (!$serie) {
            throw ValidationException::withMessages([
                'partidas' => 'Configure una serie para Notas de Venta Venta antes de generar la renovación.',
            ]);
        }

        return $serie;
    }

    private function maxFecha($fechaActual, $candidata)
    {
        return !$fechaActual || $candidata->greaterThan($fechaActual) ? $candidata : $fechaActual;
    }
}
