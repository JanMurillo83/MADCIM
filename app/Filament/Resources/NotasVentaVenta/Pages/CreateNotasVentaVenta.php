<?php

namespace App\Filament\Resources\NotasVentaVenta\Pages;

use App\Filament\Resources\NotasVentaVenta\NotasVentaVentaResource;
use App\Models\Clientes;
use App\Models\Productos;
use Carbon\Carbon;
use DomainException;
use App\Services\InventarioMovimientoService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class CreateNotasVentaVenta extends CreateRecord
{
    protected static string $resource = NotasVentaVentaResource::class;

    public ?array $notaGuardada = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancelar_captura')
                ->label('Cancelar')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancelar captura')
                ->modalDescription('¿Deseas cancelar la captura y regresar al listado? Se perderán los datos no guardados.')
                ->modalSubmitActionLabel('Sí, cancelar')
                ->action(fn () => $this->cancelarCaptura()),
            Action::make('guardar')
                ->label('Guardar')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Confirmar nota de venta')
                ->modalSubmitActionLabel('Guardar')
                ->modalCancelActionLabel('Revisar')
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

            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('No se pudo guardar la nota')
                ->body($mensaje ?: 'Revise los datos capturados e intente nuevamente.')
                ->persistent()
                ->send();
        }
    }

    public function notaGuardadaAction(): Action
    {
        return Action::make('notaGuardada')
            ->modalHeading('Nota guardada')
            ->modalDescription(fn (): HtmlString => new HtmlString(
                '<div class="space-y-2">'
                . '<p>Folio: <strong>' . e($this->notaGuardada['folio'] ?? '-') . '</strong></p>'
                . '<p>Cliente: <strong>' . e($this->notaGuardada['cliente'] ?? '-') . '</strong></p>'
                . '<p>Total: <strong>$' . number_format((float) ($this->notaGuardada['total'] ?? 0), 2) . '</strong></p>'
                . '<p>Condición de pago: <strong>' . e($this->notaGuardada['condicion_pago'] ?? '-') . '</strong></p>'
                . '<p>El pago se realizará posteriormente en Caja.</p>'
                . '</div>'
            ))
            ->modalSubmitActionLabel('OK')
            ->modalCancelAction(false)
            ->modalCloseButton(false)
            ->action(function (): void {
                $this->limpiarCaptura();
            });
    }

    public function limpiarCaptura(): void
    {
        $this->record = null;
        $this->form->model($this->getResource()::getModel());
        $this->fillForm();
        $this->notaGuardada = null;
    }

    public function cancelarCaptura(): void
    {
        $this->redirect($this->getResource()::getUrl('index'));
    }

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
        $data['saldo_pendiente'] = round((float) ($data['total'] ?? 0), 2);

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

        $record->load('cliente');
        $this->notaGuardada = [
            'folio' => trim(($record->serie ?? '') . '-' . ($record->folio ?? $record->id), '-'),
            'cliente' => $record->cliente?->nombre ?? 'N/A',
            'total' => (float) $record->total,
            'condicion_pago' => $record->condicion_pago === 'credito' ? 'Crédito' : 'Contado',
        ];
        $this->unmountAction(canCancelParentActions: false);
        $this->mountAction('notaGuardada');
        $this->halt();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->hidden();
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->hidden();
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
