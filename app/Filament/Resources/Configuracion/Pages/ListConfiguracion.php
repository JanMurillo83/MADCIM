<?php

namespace App\Filament\Resources\Configuracion\Pages;

use App\Filament\Resources\Configuracion\ConfiguracionResource;
use App\Services\ResetOperationalDataService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListConfiguracion extends ListRecords
{
    protected static string $resource = ConfiguracionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetOperationalData')
                ->label('Reiniciar datos operativos')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reiniciar datos operativos')
                ->modalDescription('Se eliminaran documentos, partidas, pagos, caja, compras, logistica, devoluciones, direcciones y folios. Se conservaran clientes, proveedores, productos, usuarios, sucursales, configuracion y catalogos SAT.')
                ->modalSubmitActionLabel('Si, reiniciar datos')
                ->action(function (): void {
                    $tablesReset = app(ResetOperationalDataService::class)->reset();

                    Notification::make()
                        ->success()
                        ->title('Datos operativos reiniciados')
                        ->body("Se limpiaron {$tablesReset} tablas.")
                        ->send();
                }),
        ];
    }
}
