<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Pagos;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CobroNotaService
{
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

            $formaPago = (string) ($data['forma_pago'] ?? '01');
            $importe = round($saldo, 2);
            $recibido = $formaPago === '01'
                ? round((float) ($data['importe_recibido'] ?? 0), 2)
                : $importe;

            if ($recibido < $importe) {
                throw new RuntimeException('El importe recibido es menor al saldo pendiente.');
            }

            $cajaId = null;
            if ($formaPago === '01') {
                $cajaId = Caja::query()
                    ->where('estatus', 'Abierta')
                    ->where('usuario_apertura_id', $userId)
                    ->lockForUpdate()
                    ->value('id');

                if (!$cajaId) {
                    throw new RuntimeException('No tienes una caja abierta para recibir efectivo.');
                }
            }

            return Pagos::create([
                'documento_tipo' => $documentoTipo,
                'documento_id' => $documento->id,
                'cliente_id' => $documento->cliente_id,
                'fecha_pago' => $data['fecha_pago'] ?? now()->toDateString(),
                'fecha_pago_hora' => now(),
                'forma_pago' => $formaPago,
                'importe' => $importe,
                'importe_recibido' => $recibido,
                'cambio' => round($recibido - $importe, 2),
                'referencia' => $data['referencia'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'user_id' => $userId,
                'caja_id' => $cajaId,
            ]);
        });
    }
}
