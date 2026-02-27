<?php

namespace App\Filament\Resources\NotasEnvio\Pages;

use App\Filament\Resources\NotasEnvio\NotasEnvioResource;
use App\Models\Clientes;
use App\Models\NotaEnvio;
use App\Models\NotasVentaRenta;
use App\Models\RegistroRenta;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNotasEnvio extends CreateRecord
{
    protected static string $resource = NotasEnvioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['folio'] = (NotaEnvio::max('folio') ?? 0) + 1;
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $nota = NotasVentaRenta::with('cliente')->find($record->nota_venta_renta_id);

        if (!$nota) return;

        $cliente = $nota->cliente;
        $fechaEmision = Carbon::parse($nota->fecha_emision);
        $diasRenta = $nota->dias_renta ?? 30;
        $fechaVencimiento = $nota->fecha_vencimiento ?? $fechaEmision->copy()->addDays($diasRenta)->toDateString();

        foreach ($record->partidas as $partida) {
            RegistroRenta::create([
                'nota_venta_renta_id' => $nota->id,
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

        // Abrir ticket de nota de envío en nueva pestaña
        $url = route('notas-envio.pdf.ticket', $record->id);
        $this->js("window.open('{$url}', '_blank')");
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
