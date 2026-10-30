<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Resources\Pagos\PagosResource;
use App\Models\Caja;
use App\Models\FacturasCfdi;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePagos extends CreateRecord
{
    protected static string $resource = PagosResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $documento = match ($data['documento_tipo'] ?? null) {
            'notas_venta_renta' => NotasVentaRenta::query()->find($data['documento_id'] ?? null),
            'notas_venta_venta' => NotasVentaVenta::query()->find($data['documento_id'] ?? null),
            'facturas_cfdi' => FacturasCfdi::query()->find($data['documento_id'] ?? null),
            default => null,
        };

        $data['caja_id'] = Caja::query()
            ->where('estatus', 'Abierta')
            ->where('sucursal_id', $documento?->sucursal_id)
            ->value('id');

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
