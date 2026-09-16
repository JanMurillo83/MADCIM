<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Models\ClienteDireccionEntrega;
use App\Models\Clientes;
use App\Models\CierreDevolucionRenta;
use App\Models\RegistroRenta;
use App\Services\CierreDevolucionRentaService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class ConsultaDevoluciones extends Page
{
    use HasRolePageAccess;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationLabel = 'Consulta de Devoluciones';
    protected static ?string $title = 'Consulta de Devoluciones';
    protected static string|null|\UnitEnum $navigationGroup = 'Devoluciones';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.consulta-devoluciones';

    public ?int $cliente_id = null;
    public ?int $direccion_entrega_id = null;

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
        try {
            $resultado = app(CierreDevolucionRentaService::class)->cerrarPorObra(
                $clienteId,
                $direccionEntregaId,
                userId: Auth::id(),
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
                ->body('La obra no se cerró. Revise que tenga rentas pendientes y vuelva a intentarlo.')
                ->persistent()
                ->send();
        }
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
