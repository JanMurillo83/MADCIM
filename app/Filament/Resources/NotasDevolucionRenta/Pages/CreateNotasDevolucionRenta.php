<?php

namespace App\Filament\Resources\NotasDevolucionRenta\Pages;

use App\Filament\Resources\NotasDevolucionRenta\NotasDevolucionRentaResource;
use App\Filament\Resources\NotasDevolucionRenta\Schemas\NotasDevolucionRentaForm;
use App\Models\NotaEnvio;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateNotasDevolucionRenta extends CreateRecord
{
    protected static string $resource = NotasDevolucionRentaResource::class;

    public function mount(): void
    {
        parent::mount();

        $notaVentaRentaId = request()->integer('nota_venta_renta_id') ?: null;
        if (!$notaVentaRentaId) {
            return;
        }

        $datos = NotasDevolucionRentaForm::obtenerDatosIniciales($notaVentaRentaId);
        $this->form->fill(array_merge($this->data ?? [], [
            'nota_venta_renta_id' => $notaVentaRentaId,
            'cliente_id' => $datos['cliente_id'],
            'partidas' => $datos['partidas'],
        ]));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $notaVentaRentaId = (int) ($data['nota_venta_renta_id'] ?? 0);
        $tieneEnvioEntregado = $notaVentaRentaId > 0
            && NotaEnvio::query()
                ->where('nota_venta_renta_id', $notaVentaRentaId)
                ->where('estatus', 'Entregada')
                ->whereHas('partidas', fn ($query) => $query->whereRaw('cantidad_devuelta < cantidad'))
                ->exists();

        if (!$tieneEnvioEntregado) {
            throw ValidationException::withMessages([
                'nota_venta_renta_id' => 'No se puede crear la Nota de Devolución hasta que la Nota de Envío esté marcada como Entregada.',
            ]);
        }

        $data['user_id'] = Auth::id();
        $data['estatus'] = 'Pendiente';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->alpineClickHandler(
                'window.location.href = ' . json_encode($this->getResource()::getUrl('index'))
            );
    }

    protected function afterCreate(): void
    {
        $this->record->aplicarCantidadesRecogidas();

        $url = route('notas-devolucion-renta.pdf.ticket', $this->record->id);
        $this->js("window.open('{$url}', '_blank')");
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
