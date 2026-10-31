<?php

namespace App\Services;

use App\Enums\TipoNotaRenta;
use App\Models\DocumentoSerie;
use App\Models\NotaEnvio;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\NotaVentaVentaPartidas;
use App\Models\RegistroRenta;
use App\Support\Impuestos;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RenovacionRentaService
{
    public function importeSugerido(NotaEnvio $notaEnvio, int $dias): float
    {
        $envio = NotaEnvio::query()
            ->with(['notaVentaRenta', 'partidas.partidaRenta.producto'])
            ->findOrFail($notaEnvio->id);
        $importe = 0.0;

        foreach ($envio->partidas as $partidaEnvio) {
            $partidaRenta = $partidaEnvio->partidaRenta;
            if (! $partidaRenta) {
                continue;
            }

            $cantidad = RegistroRenta::query()
                ->where('nota_envio_partida_id', $partidaEnvio->id)
                ->where('estado', 'Activo')
                ->whereRaw('cantidad > COALESCE(cantidad_devuelta, 0)')
                ->get()
                ->sum(fn (RegistroRenta $registro): float =>
                    max(0, (float) $registro->cantidad - (float) ($registro->cantidad_devuelta ?? 0))
                );
            if ($cantidad <= 0) {
                continue;
            }

            $tipo = TipoNotaRenta::tryFrom($partidaRenta->tipo_nota_renta ?? $envio->notaVentaRenta?->tipo_nota_renta ?? 'equipo')
                ?? TipoNotaRenta::Equipo;

            if ($tipo->esMaderaM2()) {
                $metros = (float) ($partidaRenta->metros_m2 ?? 0);
                $periodo = max(1, (int) ($partidaRenta->dias_renta ?? $envio->notaVentaRenta?->dias_renta ?? 30));
                $importe += (float) $partidaRenta->valor_unitario * $metros * $dias / $periodo;
                continue;
            }

            $producto = $partidaRenta->producto;
            $tipoRenta = $tipo->esMadera() ? 'dia' : ($partidaRenta->tipo_renta ?? 'dia');
            $precio = match ($tipoRenta) {
                'semana' => (float) ($producto?->precio_renta_semana ?? 0),
                'mes' => (float) ($producto?->precio_renta_mes ?? 0),
                default => (float) ($producto?->precio_renta_dia ?? 0),
            };
            $diasPorTarifa = match ($tipoRenta) {
                'semana' => 7,
                'mes' => 30,
                default => 1,
            };

            $importe += $precio * $cantidad * $dias / $diasPorTarifa;
        }

        return round($importe, 2);
    }

    public function renovar(NotaEnvio $notaEnvio, int $dias, float $importe, ?int $userId = null): NotasVentaVenta
    {
        if ($dias < 1) {
            throw ValidationException::withMessages(['dias' => 'Capture al menos un día para extender la renta.']);
        }
        if ($importe <= 0) {
            throw ValidationException::withMessages(['importe' => 'Capture un importe mayor a cero para el cobro pendiente.']);
        }

        return DB::transaction(function () use ($notaEnvio, $dias, $importe, $userId): NotasVentaVenta {
            $envio = NotaEnvio::query()
                ->with(['notaVentaRenta.cliente', 'partidas.partidaRenta.producto', 'partidas.producto'])
                ->lockForUpdate()
                ->findOrFail($notaEnvio->id);
            $nota = $envio->notaVentaRenta;

            if (! $nota || $nota->estatus !== 'Activa' || $envio->estatus === 'Cancelada') {
                throw ValidationException::withMessages(['dias' => 'Solo se puede renovar un envío de una renta activa.']);
            }

            $partidasCobro = [];
            $vencimientosEnvio = [];
            foreach ($envio->partidas as $partidaEnvio) {
                $registros = RegistroRenta::query()
                    ->where('nota_envio_partida_id', $partidaEnvio->id)
                    ->where('estado', 'Activo')
                    ->whereRaw('cantidad > COALESCE(cantidad_devuelta, 0)')
                    ->lockForUpdate()
                    ->get();

                if ($registros->isEmpty()) {
                    continue;
                }

                $partidaRenta = $partidaEnvio->partidaRenta;
                if (! $partidaRenta) {
                    throw ValidationException::withMessages([
                        'dias' => 'No se encontró la partida de renta vinculada al envío.',
                    ]);
                }

                $tipo = TipoNotaRenta::tryFrom($partidaRenta->tipo_nota_renta ?? $nota->tipo_nota_renta ?? 'equipo')
                    ?? TipoNotaRenta::Equipo;
                $cantidadActiva = $registros->sum(fn (RegistroRenta $registro): float =>
                    max(0, (float) $registro->cantidad - (float) ($registro->cantidad_devuelta ?? 0))
                );
                if ($cantidadActiva <= 0) {
                    continue;
                }

                $vencePartida = null;
                foreach ($registros as $registro) {
                    $fechaBase = $registro->fecha_vencimiento
                        ?? $partidaEnvio->fecha_vencimiento
                        ?? $partidaRenta->fecha_vencimiento
                        ?? $nota->fecha_vencimiento
                        ?? Carbon::today();
                    $nuevoVencimiento = Carbon::parse($fechaBase)->addDays($dias);

                    $registro->update(['fecha_vencimiento' => $nuevoVencimiento->toDateString()]);
                    $vencePartida = $this->maxFecha($vencePartida, $nuevoVencimiento);
                }

                if ($vencePartida) {
                    $partidaEnvio->update(['fecha_vencimiento' => $vencePartida->toDateString()]);
                    $vencePartidaContrato = RegistroRenta::query()
                        ->where('nota_venta_renta_id', $nota->id)
                        ->where('estado', 'Activo')
                        ->whereRaw('cantidad > COALESCE(cantidad_devuelta, 0)')
                        ->whereHas('notaEnvioPartida', fn ($query) => $query
                            ->where('nota_venta_renta_partida_id', $partidaRenta->id))
                        ->max('fecha_vencimiento');
                    if ($vencePartidaContrato) {
                        $partidaRenta->update(['fecha_vencimiento' => $vencePartidaContrato]);
                    }
                    $vencimientosEnvio[] = $vencePartida;
                }

                $partidasCobro[] = [
                    'item' => (string) $partidaRenta->item,
                    'descripcion' => (string) ($partidaRenta->descripcion ?: $partidaEnvio->descripcion),
                    'tipo_nota_renta' => $tipo->value,
                ];
            }

            if ($partidasCobro === []) {
                throw ValidationException::withMessages([
                    'dias' => 'El envío no tiene piezas activas pendientes de devolución para renovar.',
                ]);
            }

            $vencimientoEnvio = collect($vencimientosEnvio)->reduce(
                fn ($maximo, Carbon $fecha) => $this->maxFecha($maximo, $fecha),
            );
            if ($vencimientoEnvio) {
                $envio->update(['fecha_vencimiento' => $vencimientoEnvio->toDateString()]);
            }

            $vencimientoNota = RegistroRenta::query()
                ->where('nota_venta_renta_id', $nota->id)
                ->where('estado', 'Activo')
                ->whereRaw('cantidad > COALESCE(cantidad_devuelta, 0)')
                ->max('fecha_vencimiento');
            if ($vencimientoNota) {
                $fechaFin = Carbon::parse($vencimientoNota);
                $nota->update(['fecha_vencimiento' => $fechaFin->toDateString()]);
            }

            return $this->crearCobroPendiente($nota, $partidasCobro, $dias, $importe, $userId ?? Auth::id());
        });
    }

    /** @param array<int, array{item: string, descripcion: string, tipo_nota_renta: string}> $partidas */
    private function crearCobroPendiente(NotasVentaRenta $nota, array $partidas, int $dias, float $importe, ?int $userId): NotasVentaVenta
    {
        $serie = DocumentoSerie::query()
            ->where('documento_tipo', 'notas_venta_venta')
            ->orderBy('id')
            ->value('serie');
        if (! $serie) {
            throw ValidationException::withMessages([
                'dias' => 'Configure una serie para Notas de Venta antes de registrar el cobro de renovación.',
            ]);
        }

        $importe = round($importe, 2);
        $soloMaderaM2 = collect($partidas)->every(fn (array $partida): bool =>
            (TipoNotaRenta::tryFrom($partida['tipo_nota_renta']) ?? TipoNotaRenta::Equipo)->esMaderaM2()
        );
        $desglose = $soloMaderaM2
            ? ['subtotal' => $importe, 'iva' => 0.0]
            : Impuestos::desglosarIvaIncluido($importe);
        $subtotal = $desglose['subtotal'];
        $impuestos = $desglose['iva'];
        $total = round($subtotal + $impuestos, 2);

        $venta = (new NotasVentaVenta([
            'cliente_id' => $nota->cliente_id,
            'sucursal_id' => $nota->sucursal_id,
            'user_id' => $userId,
            'serie' => $serie,
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
        $venta->save();

        $descripciones = collect($partidas)->pluck('descripcion')->filter()->unique()->implode(', ');
        NotaVentaVentaPartidas::create([
            'nota_venta_venta_id' => $venta->id,
            'cantidad' => 1,
            'item' => $partidas[0]['item'] ?: 'RENTA',
            'descripcion' => mb_substr("Extensión de renta por {$dias} días" . ($descripciones ? " - {$descripciones}" : ''), 0, 255),
            'valor_unitario' => $importe,
            'subtotal' => $subtotal,
            'impuestos' => $impuestos,
            'total' => $total,
        ]);

        return $venta->load('partidas');
    }

    private function maxFecha(?Carbon $actual, Carbon $candidata): Carbon
    {
        return ! $actual || $candidata->greaterThan($actual) ? $candidata : $actual;
    }
}
