<?php

namespace App\Filament\Resources\Cotizaciones\Pages;

use App\Filament\Resources\Cotizaciones\CotizacionesResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCotizaciones extends EditRecord
{
    protected static string $resource = CotizacionesResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancelar')
                ->label('Cancelar Cotización')
                ->icon('fas-ban')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancelar Cotización')
                ->modalDescription('¿Estás seguro de que deseas cancelar esta cotización? Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, cancelar')
                ->visible(fn () => $this->record->estatus === 'Activa')
                ->action(function () {
                    $this->record->update(['estatus' => 'Cancelada']);

                    Notification::make()
                        ->title('Cotización cancelada')
                        ->body("La cotización {$this->record->serie}-{$this->record->folio} ha sido cancelada exitosamente.")
                        ->success()
                        ->send();

                    return redirect($this->getResource()::getUrl('index'));
                }),
            //DeleteAction::make(),
        ];
    }
}
