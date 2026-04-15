<?php

namespace App\Filament\Resources\NotasDevolucionRenta\Pages;

use App\Filament\Resources\NotasDevolucionRenta\NotasDevolucionRentaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNotasDevolucionRenta extends CreateRecord
{
    protected static string $resource = NotasDevolucionRentaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['estatus'] = 'Pendiente';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        $url = route('notas-devolucion-renta.pdf.ticket', $this->record->id);
        $this->js("window.open('{$url}', '_blank')");
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
