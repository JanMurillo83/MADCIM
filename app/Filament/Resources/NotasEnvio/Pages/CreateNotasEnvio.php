<?php

namespace App\Filament\Resources\NotasEnvio\Pages;

use App\Filament\Resources\NotasEnvio\NotasEnvioResource;
use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotasVentaRenta;
use App\Models\Productos;
use App\Models\RegistroRenta;
use App\Services\InventarioMovimientoService;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateNotasEnvio extends CreateRecord
{
    protected static string $resource = NotasEnvioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['nota_venta_venta_id']);

        // Solo persiste columnas propias de nota_envio_partidas.
        $data['partidas'] = collect($data['partidas'] ?? [])
            ->map(function (array $partida): array {
                unset($partida['m2_cubre']);
                unset($partida['observaciones']);

                return $partida;
            })
            ->all();

        $nota = NotasVentaRenta::with(['partidas', 'desgloseM2'])->find($data['nota_venta_renta_id'] ?? null);
        foreach ($data['partidas'] as $indice => $partida) {
            if ((int) ($partida['dias_renta'] ?? 0) < 1) {
                throw ValidationException::withMessages([
                    "partidas.{$indice}.dias_renta" => 'Capture los días de renta de la partida.',
                ]);
            }

            if (empty($partida['fecha_vencimiento'])) {
                throw ValidationException::withMessages([
                    "partidas.{$indice}.fecha_vencimiento" => 'Capture la fecha de vencimiento de la partida.',
                ]);
            }
        }
        if ($nota) {
            $partidasM2 = $nota->partidas->filter(
                fn ($partida) => \App\Enums\TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? '')?->esMaderaM2()
            );
            if ($partidasM2->count() === 1 && $nota->partidas->count() === 1) {
                $partidaM2Id = $partidasM2->first()->id;
                $data['partidas'] = collect($data['partidas'] ?? [])
                    ->map(function (array $partida) use ($partidaM2Id): array {
                        if (empty($partida['nota_venta_renta_partida_id'])) {
                            $partida['nota_venta_renta_partida_id'] = $partidaM2Id;
                        }

                        return $partida;
                    })
                    ->all();
            }
            foreach ($partidasM2 as $partidaOrigen) {
                $objetivoM2 = (float) $partidaOrigen->metros_m2;
                if ($objetivoM2 <= 0) {
                    continue;
                }
                $cubiertoM2 = collect($data['partidas'] ?? [])
                    ->filter(fn (array $partida) => (int) ($partida['nota_venta_renta_partida_id'] ?? 0) === (int) $partidaOrigen->id)
                    ->sum(function (array $partida): float {
                        $producto = Productos::find($partida['producto_id'] ?? null);
                        return (float) ($partida['cantidad'] ?? 0) * (float) ($producto?->m2_cubre ?? 0);
                    });
                $yaEnviadoM2 = NotaEnvioPartida::query()
                    ->whereHas('notaEnvio', fn ($query) => $query->where('nota_venta_renta_id', $nota->id))
                    ->get(['producto_id', 'cantidad', 'nota_venta_renta_partida_id'])
                    ->filter(function ($envioPartida) use ($partidaOrigen): bool {
                        $producto = Productos::find($envioPartida->producto_id);
                        return (int) $envioPartida->nota_venta_renta_partida_id === (int) $partidaOrigen->id
                            || (float) ($producto?->m2_cubre ?? 0) > 0;
                    })
                    ->sum(fn ($partida): float => (float) $partida->cantidad * (float) (Productos::find($partida->producto_id)?->m2_cubre ?? 0));

                $limiteM2 = $objetivoM2 + 1.0;
                if ($yaEnviadoM2 + $cubiertoM2 > $limiteM2 + 0.0001) {
                    $mensaje = sprintf(
                        'La captura excede el límite permitido de %.2f M2. El objetivo es %.2f M2 y el margen autorizado es de 1.00 M2.',
                        $limiteM2,
                        $objetivoM2,
                    );
                    Notification::make()
                        ->danger()
                        ->title('No se pudo crear la Nota de Envío')
                        ->body($mensaje)
                        ->persistent()
                        ->send();
                    throw ValidationException::withMessages([
                        'partidas' => $mensaje,
                    ]);
                }
            }
        }

        $data['folio'] = (NotaEnvio::max('folio') ?? 0) + 1;
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $nota = NotasVentaRenta::with('cliente')->find($record->nota_venta_renta_id);

        if (!$nota) return;

        try {
            DB::transaction(function () use ($record, $nota): void {
                $cliente = $nota->cliente;
                $fechaEmision = Carbon::parse($nota->fecha_emision);

                $referencia = $record->serie . $record->folio;

                foreach ($record->partidas as $partida) {
                    $producto = Productos::find($partida->producto_id);
                    if (!$producto) {
                        throw new \RuntimeException("No se encontró el producto {$partida->producto_id}.");
                    }

                    $diasRenta = max(1, (int) $partida->dias_renta);
                    $fechaVencimiento = $partida->fecha_vencimiento?->toDateString();
                    if (!$fechaVencimiento) {
                        throw new \RuntimeException('La Nota de Envío requiere fecha de vencimiento.');
                    }

                    InventarioMovimientoService::salida(
                        productoId: $producto->id,
                        cantidad: (float) $partida->cantidad,
                        motivo: "Envío de renta {$referencia}",
                        documentoReferencia: $referencia
                    );

                    RegistroRenta::create([
                        'nota_venta_renta_id' => $nota->id,
                        'nota_envio_partida_id' => $partida->id,
                        'cliente_id' => $nota->cliente_id,
                        'cliente_nombre' => $cliente->nombre ?? '',
                        'cliente_contacto' => $cliente->contacto ?? null,
                        'cliente_telefono' => $cliente->telefono ?? null,
                        'cliente_direccion' => $cliente ? implode(', ', array_filter([
                            $cliente->calle, $cliente->exterior, $cliente->colonia,
                            $cliente->municipio, $cliente->estado,
                        ])) : null,
                        'producto_id' => $partida->producto_id,
                        'cantidad' => $partida->cantidad,
                        'dias_renta' => $diasRenta,
                        'fecha_renta' => $fechaEmision->toDateString(),
                        'fecha_vencimiento' => $fechaVencimiento,
                        'importe_renta' => 0,
                        'importe_deposito' => $nota->deposito ?? 0,
                        'estado' => 'Activo',
                        'observaciones' => $partida->descripcion,
                    ]);
                }

                // Crear el envío solo confirma que salió del almacén. La entrega se
                // confirma después mediante la acción "Marcar Entregada".
                $record->update([
                    'estatus' => 'Enviada',
                    'estado_renta' => $record->nota_venta_renta_id ? 'Pendiente' : null,
                ]);

            });
        } catch (\Throwable $exception) {
            report($exception);
            Notification::make()
                ->danger()
                ->title('No se pudo procesar el envío')
                ->body($exception->getMessage())
                ->persistent()
                ->send();
            throw $exception;
        }

        // Abrir ticket de nota de envío en nueva pestaña
        $url = route('notas-envio.pdf.ticket', $record->id);
        $this->js("window.open('{$url}', '_blank')");
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $partidas = $data['partidas'] ?? [];
            unset($data['partidas']);

            $record = new NotaEnvio($data);
            $record->save();

            foreach ($partidas as $partida) {
                NotaEnvioPartida::create([
                    'nota_envio_id' => $record->id,
                    'nota_venta_renta_partida_id' => $partida['nota_venta_renta_partida_id'] ?? null,
                    'producto_id' => $partida['producto_id'] ?? null,
                    'descripcion' => $partida['descripcion'] ?? null,
                    'cantidad' => $partida['cantidad'] ?? 0,
                    'dias_renta' => $partida['dias_renta'] ?? null,
                    'fecha_vencimiento' => $partida['fecha_vencimiento'] ?? null,
                ]);
            }

            return $record;
        } catch (\Throwable $exception) {
            report($exception);
            Notification::make()
                ->danger()
                ->title('No se pudo crear la Nota de Envío')
                ->body($exception->getMessage())
                ->persistent()
                ->send();
            throw $exception;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
