<?php

namespace App\Filament\Resources\NotasVentaVenta\Pages;

use App\Filament\Resources\NotasVentaVenta\NotasVentaVentaResource;
use App\Models\Clientes;
use App\Models\Productos;
use Carbon\Carbon;
use DomainException;
use App\Services\InventarioMovimientoService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateNotasVentaVenta extends CreateRecord
{
    protected static string $resource = NotasVentaVentaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $cliente = Clientes::find($data['cliente_id'] ?? null);
        $condicionPago = $data['condicion_pago'] ?? 'contado';

        try {
            $cliente?->validarCreacionNota($condicionPago);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'cliente_id' => $exception->getMessage(),
            ]);
        }

        $fechaEmision = Carbon::parse($data['fecha_emision'] ?? now());
        $data['fecha_vencimiento_pago'] = $condicionPago === 'credito'
            ? $fechaEmision->copy()->addDays(max(1, (int) ($cliente?->dias_credito ?? 0)))->toDateString()
            : null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $referencia = $record->serie . $record->folio;

        foreach ($record->partidas as $partida) {
            $producto = Productos::find($partida->item);
            if (! $producto) {
                continue;
            }

            InventarioMovimientoService::salida(
                productoId: $producto->id,
                cantidad: (float) $partida->cantidad,
                motivo: "Venta generada en nota {$referencia}",
                documentoReferencia: $referencia
            );
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
