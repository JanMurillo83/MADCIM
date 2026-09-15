<?php

namespace App\Filament\Resources\NotasDevolucionRenta\Pages;

use App\Filament\Resources\NotasDevolucionRenta\NotasDevolucionRentaResource;
use App\Models\ClienteDireccionEntrega;
use App\Models\RegistroRenta;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateNotasDevolucionRenta extends CreateRecord
{
    protected static string $resource = NotasDevolucionRentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancelar_captura')
                ->label('Cancelar')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancelar captura')
                ->modalDescription('¿Deseas cancelar la captura y regresar al listado? Se perderán los datos no guardados.')
                ->modalSubmitActionLabel('Sí, cancelar')
                ->action(fn () => $this->cancelarCaptura()),
            Action::make('guardar')
                ->label('Guardar')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->guardarCaptura()),
        ];
    }

    public function guardarCaptura(): void
    {
        try {
            $this->create();
        } catch (ValidationException $exception) {
            $mensaje = collect($exception->errors())
                ->flatten()
                ->filter()
                ->implode(' ');

            Notification::make()
                ->danger()
                ->title('No se pudo guardar la nota')
                ->body($mensaje ?: 'Revise los datos capturados e intente nuevamente.')
                ->persistent()
                ->send();
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('Error al guardar la nota')
                ->body('Ocurrió un error al guardar la devolución. Revise los datos e intente nuevamente.')
                ->persistent()
                ->send();
        }
    }

    public function cancelarCaptura(): void
    {
        $this->redirect($this->getResource()::getUrl('index'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $partidas = $data['partidas'] ?? [];
        if (empty($partidas)) {
            $partidas = $this->form->getRawState()['partidas'] ?? [];
        }

        $partidas = array_values(array_filter(
            $partidas,
            fn (array $partida): bool => (float) ($partida['cantidad_a_devolver'] ?? 0) > 0,
        ));

        $data['partidas'] = $partidas;
        $clienteId = (int) ($data['cliente_id'] ?? 0);
        $direccionId = (int) ($data['direccion_entrega_id'] ?? 0);
        $direccionValida = $direccionId > 0
            && ClienteDireccionEntrega::query()
                ->whereKey($direccionId)
                ->where('cliente_id', $clienteId)
                ->where('activa', true)
                ->exists();

        if (!$clienteId || !$direccionValida) {
            throw ValidationException::withMessages([
                'direccion_entrega_id' => 'Seleccione una obra válida para el cliente.',
            ]);
        }

        if (empty($partidas)) {
            throw ValidationException::withMessages([
                'partidas' => 'Capture al menos una cantidad a devolver mayor que cero.',
            ]);
        }

        $totalADevolver = 0.0;
        foreach ($partidas as $index => $partida) {
            $enviada = (float) ($partida['cantidad_enviada'] ?? 0);
            $devuelta = (float) ($partida['cantidad_devuelta'] ?? 0);
            $aDevolver = (float) ($partida['cantidad_a_devolver'] ?? 0);
            $totalADevolver += $aDevolver;
            $pendienteActual = (float) RegistroRenta::query()
                ->where('cliente_id', $clienteId)
                ->where('producto_id', $partida['producto_id'] ?? 0)
                ->whereHas('notaVentaRenta', fn ($query) => $query->where('direccion_entrega_id', $direccionId))
                ->selectRaw('COALESCE(SUM(cantidad), 0) - COALESCE(SUM(cantidad_devuelta), 0) as pendiente')
                ->value('pendiente');

            if ($aDevolver < 0 || $aDevolver > max(0, $pendienteActual)) {
                throw ValidationException::withMessages([
                    "partidas.{$index}.cantidad_a_devolver" => 'La cantidad a devolver no puede superar la cantidad pendiente.',
                ]);
            }
        }

        if ($totalADevolver <= 0) {
            throw ValidationException::withMessages([
                'partidas' => 'Capture al menos una cantidad a devolver.',
            ]);
        }

        $data['user_id'] = Auth::id();
        $data['fecha_emision'] ??= now()->toDateString();
        $data['estatus'] = 'Pendiente';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->hidden();
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->hidden();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('guardar_abajo')
                ->label('Guardar')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->guardarCaptura()),
            Action::make('cancelar_abajo')
                ->label('Cancelar')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancelar captura')
                ->modalDescription('¿Deseas cancelar la captura y regresar al listado? Se perderán los datos no guardados.')
                ->modalSubmitActionLabel('Sí, cancelar')
                ->action(fn () => $this->cancelarCaptura()),
        ];
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
