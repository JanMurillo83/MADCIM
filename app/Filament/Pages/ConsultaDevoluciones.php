<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Models\ClienteDireccionEntrega;
use App\Models\Clientes;
use App\Models\CierreDevolucionRenta;
use App\Models\NotasVentaRenta;
use App\Models\RegistroRenta;
use App\Services\CierreDevolucionRentaService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class ConsultaDevoluciones extends Page implements HasActions
{
    use HasRolePageAccess;
    use InteractsWithActions;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationLabel = 'Consulta de Devoluciones';
    protected static ?string $title = 'Consulta de Devoluciones';
    protected static string|null|\UnitEnum $navigationGroup = 'Devoluciones';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.consulta-devoluciones';

    public ?int $cliente_id = null;
    public ?int $direccion_entrega_id = null;
    public ?int $clienteCierreId = null;
    public ?int $direccionCierreId = null;

    #[Computed]
    public function clientes(): Collection
    {
        return Clientes::query()
            ->where(function ($query) {
                $query->whereHas('notasVentaRenta', function ($notaQuery) {
                    $notaQuery
                        ->whereNotIn('estatus', ['Cancelada', 'Devuelta', 'Vendida'])
                        ->whereHas('registrosRenta');
                })->orWhereIn('id', CierreDevolucionRenta::query()
                    ->whereIn('estatus', ['Pendiente', 'PendienteCaja'])
                    ->select('cliente_id'));
            })
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn ($cliente) => [$cliente->id => $cliente->nombre]);
    }

    #[Computed]
    public function direcciones(): Collection
    {
        if (!$this->cliente_id) {
            return collect();
        }

        return ClienteDireccionEntrega::query()
            ->where('cliente_id', $this->cliente_id)
            ->orderBy('nombre_direccion')
            ->get()
            ->mapWithKeys(fn ($direccion) => [
                $direccion->id => $direccion->nombre_direccion . ' - ' . $direccion->direccion_completa,
            ]);
    }

    public function updatedClienteId(): void
    {
        $this->direccion_entrega_id = null;
    }

    public function cerrarObra(int $clienteId, int $direccionEntregaId): void
    {
        $this->clienteCierreId = $clienteId;
        $this->direccionCierreId = $direccionEntregaId;
        $this->mountAction('confirmarCierreObra');
    }

    public function confirmarCierreObraAction(): Action
    {
        return Action::make('confirmarCierreObra')
            ->modalHeading('Vista previa del cierre de obra')
            ->modalWidth('7xl')
            ->modalSubmitActionLabel('Confirmar y procesar cierre')
            ->form(function (): array {
                $nota = NotasVentaRenta::query()
                    ->where('cliente_id', $this->clienteCierreId)
                    ->where('direccion_entrega_id', $this->direccionCierreId)
                    ->where('estatus', '!=', 'Cancelada')
                    ->orderBy('id')
                    ->first();

                $resumenData = $nota
                    ? app(CierreDevolucionRentaService::class)->obtenerResumenPorObra($nota)
                    : ['rows' => [], 'totales' => []];
                $totales = $resumenData['totales'];
                $filas = collect($resumenData['rows'])->map(fn (array $row): string =>
                    $row['producto'] . ': Faltante=' . number_format((float) $row['faltante'], 2)
                    . ' x $' . number_format((float) $row['precio_unitario'], 2)
                    . ' = $' . number_format((float) $row['total'], 2)
                )->implode("\n");

                $resumen = ($filas ?: 'No hay faltantes por cobrar en esta obra.')
                    . "\n\n--- Resumen consolidado ---"
                    . "\nDepósito recibido: $" . number_format((float) ($totales['deposito'] ?? 0), 2)
                    . "\nTotal faltantes: $" . number_format((float) ($totales['total_faltantes'] ?? 0), 2)
                    . "\nDepósito aplicado: $" . number_format((float) ($totales['deposito_aplicado'] ?? 0), 2)
                    . "\nSaldo por cobrar: $" . number_format((float) ($totales['saldo_por_cobrar'] ?? 0), 2)
                    . "\nDepósito a devolver: $" . number_format((float) ($totales['deposito_devolver'] ?? 0), 2);

                return [
                    Placeholder::make('resumen_cierre')
                        ->label('Revise antes de confirmar')
                        ->content($resumen),
                    TextInput::make('folio_interno')
                        ->label('Folio interno')
                        ->maxLength(100)
                        ->required(),
                ];
            })
            ->action(function (array $data): void {
                try {
                    $resultado = app(CierreDevolucionRentaService::class)->cerrarPorObra(
                        $this->clienteCierreId,
                        $this->direccionCierreId,
                        userId: Auth::id(),
                        folioInterno: $data['folio_interno'],
                    );

                    session(['cierre_devolucion_observaciones_nvr_' . $resultado['nota_id'] => null]);
                    session(['cierre_devolucion_resumen_nvr_' . $resultado['nota_id'] => $resultado['resumen']]);

                    $this->redirect(
                        route('notas-venta-renta.cierre-devolucion-ticket', $resultado['nota_id']),
                        navigate: false,
                    );
                } catch (\Throwable $exception) {
                    report($exception);
                    Notification::make()
                        ->danger()
                        ->title('No se pudo cerrar la obra')
                        ->body('La obra no se cerró. Revise los datos y vuelva a intentarlo.')
                        ->persistent()
                        ->send();
                }
            });
    }

    #[Computed]
    public function items(): Collection
    {
        $items = RegistroRenta::query()
            ->with([
                'producto',
                'cliente',
                'notaVentaRenta.direccionEntrega',
            ])
            ->when($this->cliente_id, fn ($query) => $query->where('cliente_id', $this->cliente_id))
            ->when($this->direccion_entrega_id, fn ($query) => $query->whereHas(
                'notaVentaRenta',
                fn ($notaQuery) => $notaQuery->where('direccion_entrega_id', $this->direccion_entrega_id)
            ))
            ->get();

        $items = collect($items);
        $cierresPendientes = CierreDevolucionRenta::query()
            ->whereIn('estatus', ['Pendiente', 'PendienteCaja'])
            ->when($this->cliente_id, fn ($query) => $query->where('cliente_id', $this->cliente_id))
            ->when($this->direccion_entrega_id, fn ($query) => $query->where('direccion_entrega_id', $this->direccion_entrega_id))
            ->get(['cliente_id', 'direccion_entrega_id'])
            ->mapWithKeys(fn ($cierre) => [$cierre->cliente_id . '-' . $cierre->direccion_entrega_id => true]);

        return $items
            ->groupBy(fn ($item) => $item->cliente_id . '-' . ($item->notaVentaRenta?->direccion_entrega_id ?? 0))
            ->filter(function (Collection $grupo, string $clave) use ($cierresPendientes): bool {
                return $grupo->contains(fn ($item) => (float) ($item->cantidad_devuelta ?? 0) < (float) $item->cantidad)
                    || $grupo->contains(fn ($item) => !in_array($item->notaVentaRenta?->estatus, ['Cancelada', 'Devuelta', 'Vendida'], true))
                    || $cierresPendientes->has($clave);
            })
            ->flatten(1)
            ->values();
    }

    #[Computed]
    public function itemsAgrupados(): Collection
    {
        return $this->items
            ->groupBy(function ($partida) {
                $direccionId = $partida->notaVentaRenta?->direccion_entrega_id ?? 0;
                return $partida->cliente_id . '-' . $direccionId;
            })
            ->map(function (Collection $itemsDireccion) {
                return $itemsDireccion->groupBy(fn ($item) => $item->producto_id ?? $item->observaciones ?? 'sin-producto')
                    ->map(function (Collection $itemsProducto) {
                        $item = clone $itemsProducto->first();
                        $item->cantidad = $itemsProducto->sum('cantidad');
                        $item->cantidad_devuelta = $itemsProducto->sum('cantidad_devuelta');
                        $item->importe_renta = $itemsProducto->sum('importe_renta');
                        $item->importe_deposito = $itemsProducto->sum('importe_deposito');

                        return $item;
                    })->values();
            });
    }
}
