<?php

namespace App\Filament\Resources\NotasDevolucionRenta\Pages;

use App\Filament\Resources\NotasDevolucionRenta\NotasDevolucionRentaResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNotasDevolucionRenta extends EditRecord
{
    protected static string $resource = NotasDevolucionRentaResource::class;

    protected function afterSave(): void
    {
        $this->record->aplicarCantidadesRecogidas();

        Notification::make()
            ->title('Cantidades aplicadas')
            ->body('La nota de envio se actualizo con las cantidades recogidas.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
