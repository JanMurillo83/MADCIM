<?php

namespace App\Filament\Resources\NotasVentaVenta\Pages;

use App\Filament\Resources\NotasVentaVenta\NotasVentaVentaResource;
use App\Models\Caja;
use App\Models\Clientes;
use App\Models\Pagos;
use App\Models\Productos;
use Carbon\Carbon;
use DomainException;
use App\Services\InventarioMovimientoService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateNotasVentaVenta extends CreateRecord
{
    protected static string $resource = NotasVentaVentaResource::class;

    public ?array $pagoCapturado = null;

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
                ->form([
                    Select::make('metodo_pago')
                        ->label('Forma de pago')
                        ->options([
                            '01' => 'Efectivo',
                            '02' => 'Cheque',
                            '03' => 'Transferencia',
                            '04' => 'Tarjeta de crédito',
                            '28' => 'Tarjeta de débito',
                        ])
                        ->default('01')
                        ->required(fn (): bool => ($this->data['condicion_pago'] ?? 'contado') === 'contado')
                        ->visible(fn (): bool => ($this->data['condicion_pago'] ?? 'contado') === 'contado'),
                    TextInput::make('importe_recibido')
                        ->label('Importe recibido')
                        ->numeric()
                        ->prefix('$')
                        ->default(fn (): float => (float) ($this->data['total'] ?? 0))
                        ->required(fn (Get $get): bool => ($this->data['condicion_pago'] ?? 'contado') === 'contado' && $get('metodo_pago') === '01')
                        ->minValue(0)
                        ->visible(fn (Get $get): bool => ($this->data['condicion_pago'] ?? 'contado') === 'contado' && $get('metodo_pago') === '01'),
                ])
                ->action(function (array $data): void {
                    if (($this->data['condicion_pago'] ?? 'contado') === 'contado' && ($data['metodo_pago'] ?? null) === '01') {
                        $importe = (float) ($this->data['total'] ?? 0);
                        $recibido = (float) ($data['importe_recibido'] ?? 0);
                        if ($recibido < $importe) {
                            throw ValidationException::withMessages([
                                'importe_recibido' => 'El importe recibido no puede ser menor al total de la nota.',
                            ]);
                        }
                    }

                    $this->pagoCapturado = $data;
                    $this->create();
                }),
        ];
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

        if ($record->condicion_pago === 'contado' && $this->pagoCapturado) {
            $formaPago = $this->pagoCapturado['metodo_pago'];
            $importe = (float) $record->total;
            $userId = Auth::id();
            $importeRecibido = $formaPago === '01'
                ? (float) ($this->pagoCapturado['importe_recibido'] ?? $importe)
                : $importe;

            Pagos::create([
                'documento_tipo' => 'notas_venta_venta',
                'documento_id' => $record->id,
                'cliente_id' => $record->cliente_id,
                'fecha_pago' => now(),
                'forma_pago' => $formaPago,
                'importe' => $importe,
                'importe_recibido' => $importeRecibido,
                'cambio' => $formaPago === '01' ? round($importeRecibido - $importe, 2) : 0,
                'referencia' => 'Pago de contado al crear nota de venta',
                'user_id' => $userId,
                'caja_id' => $formaPago === '01'
                    ? Caja::where('estatus', 'Abierta')->where('usuario_apertura_id', $userId)->value('id')
                    : null,
            ]);
        }

        $ticketUrl = route('notas-venta-venta.pdf.ticket', ['id' => $record->id]);
        $this->js("window.open('{$ticketUrl}', '_blank');");
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
