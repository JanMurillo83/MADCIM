<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Pagos;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CobroNotaService
{
    public function confirmarCredito(string $documentoTipo, int $documentoId): Model
    {
        return DB::transaction(function () use ($documentoTipo, $documentoId): Model {
            $documento = match ($documentoTipo) {
                'notas_venta_renta' => NotasVentaRenta::query()->lockForUpdate()->findOrFail($documentoId),
                'notas_venta_venta' => NotasVentaVenta::query()->lockForUpdate()->findOrFail($documentoId),
                default => throw new RuntimeException('Tipo de documento no soportado.'),
            };

            if ($documento->condicion_pago !== 'credito') {
                throw new RuntimeException('La nota no está registrada a crédito.');
            }

            if ((float) $documento->saldo_pendiente <= 0 || $documento->estatus === 'Cancelada') {
                throw new RuntimeException('La nota no tiene saldo pendiente de crédito.');
            }

            $documento->forceFill([
                'cobro_credito_confirmado_en' => now(),
            ])->saveQuietly();

            return $documento;
        });
    }

    public function cobrar(string $documentoTipo, int $documentoId, array $data, int $userId): Pagos
    {
        return DB::transaction(function () use ($documentoTipo, $documentoId, $data, $userId): Pagos {
            $documento = match ($documentoTipo) {
                'notas_venta_renta' => NotasVentaRenta::query()->lockForUpdate()->findOrFail($documentoId),
                'notas_venta_venta' => NotasVentaVenta::query()->lockForUpdate()->findOrFail($documentoId),
                default => throw new RuntimeException('Tipo de documento no soportado.'),
            };

            $saldo = round((float) $documento->saldo_pendiente, 2);
            if ($documento->estatus === 'Cancelada' || $saldo <= 0) {
                throw new RuntimeException('La nota no tiene saldo pendiente de pago.');
            }

            $lineas = array_values(array_filter($data['pagos'] ?? [], fn (array $linea): bool => (float) ($linea['importe'] ?? 0) > 0));
            if ($lineas === []) {
                throw new RuntimeException('Agrega al menos una forma de pago.');
            }

            $importeTotal = round(array_sum(array_map(fn (array $linea): float => (float) $linea['importe'], $lineas)), 2);
            if ($importeTotal > $saldo) {
                throw new RuntimeException('El pago no puede ser mayor al saldo pendiente.');
            }

            $cajaId = Caja::query()
                ->where('estatus', 'Abierta')
                ->where('usuario_apertura_id', $userId)
                ->lockForUpdate()
                ->value('id');

            if (!$cajaId && collect($lineas)->contains(fn (array $linea): bool => ($linea['forma_pago'] ?? null) === '01')) {
                throw new RuntimeException('No tienes una caja abierta para recibir efectivo.');
            }

            $primerPago = null;
            $formasPago = collect($lineas)
                ->map(fn (array $linea): string => (string) ($linea['forma_pago'] ?? '01'))
                ->unique()
                ->values();
            foreach ($lineas as $linea) {
                $formaPago = (string) ($linea['forma_pago'] ?? '01');
                $importe = round((float) $linea['importe'], 2);
                $recibido = $formaPago === '01' ? round((float) ($linea['importe_recibido'] ?? 0), 2) : $importe;

                if ($formaPago === '01' && $recibido < $importe) {
                    throw new RuntimeException('El efectivo recibido es menor al importe aplicado.');
                }

                $pago = Pagos::create([
                    'documento_tipo' => $documentoTipo,
                    'documento_id' => $documento->id,
                    'cliente_id' => $documento->cliente_id,
                    'fecha_pago' => $data['fecha_pago'] ?? now()->toDateString(),
                    'fecha_pago_hora' => now(),
                    'forma_pago' => $formaPago,
                    'importe' => $importe,
                    'importe_recibido' => $recibido,
                    'cambio' => $formaPago === '01' ? round($recibido - $importe, 2) : 0,
                    'referencia' => $data['referencia'] ?? null,
                    'observaciones' => $data['observaciones'] ?? null,
                    'user_id' => $userId,
                    'caja_id' => $cajaId,
                ]);

                $primerPago ??= $pago;
            }

            $documento->forceFill([
                'forma_pago' => $formasPago->count() === 1
                    ? $formasPago->first()
                    : $formasPago->implode(','),
            ])->saveQuietly();

            return $primerPago;
        });
    }
}
