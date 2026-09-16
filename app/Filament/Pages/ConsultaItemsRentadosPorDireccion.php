<?php

namespace App\Filament\Pages;

use App\Models\ClienteDireccionEntrega;
use App\Models\Clientes;
use App\Models\CierreDevolucionRenta;
use App\Models\RegistroRenta;
use Barryvdh\DomPDF\Facade\Pdf;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use App\Filament\Concerns\HasRolePageAccess;

class ConsultaItemsRentadosPorDireccion extends Page
{
    use HasRolePageAccess;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Productos Rentados por Dirección';
    protected static ?string $title = 'Productos Rentados por Dirección de Entrega';
    protected static string|null|\UnitEnum $navigationGroup = 'Consultas';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.consulta-items-rentados-por-direccion';

    public ?int $cliente_id = null;
    public ?int $direccion_entrega_id = null;

    public function getClientesProperty(): Collection
    {
        return Clientes::orderBy('nombre')
            ->whereHas('notasVentaRenta', function ($q) {
                $q->whereNotIn('estatus', ['Cancelada', 'Devuelta', 'Vendida'])
                    ->whereHas('registrosRenta');
            })
            ->orWhereIn('id', CierreDevolucionRenta::query()
                ->whereIn('estatus', ['Pendiente', 'PendienteCaja'])
                ->select('cliente_id'))
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->nombre]);
    }

    public function getDireccionesProperty(): Collection
    {
        if (!$this->cliente_id) {
            return collect();
        }

        return ClienteDireccionEntrega::where('cliente_id', $this->cliente_id)
            ->where('activa', true)
            ->get()
            ->mapWithKeys(fn ($d) => [$d->id => $d->nombre_direccion . ' - ' . $d->direccion_completa]);
    }

    public function updatedClienteId(): void
    {
        $this->direccion_entrega_id = null;
    }

    #[Computed]
    public function items(): Collection
    {
        if (!$this->cliente_id) {
            return collect();
        }

        $query = RegistroRenta::with(['producto', 'notaVentaRenta.direccionEntrega'])
            ->where('cliente_id', $this->cliente_id)
            ;

        if ($this->direccion_entrega_id) {
            $query->whereHas('notaVentaRenta', function ($q) {
                $q->where('direccion_entrega_id', $this->direccion_entrega_id);
            });
        }

        $items = collect($query->get());
        $cierresPendientes = CierreDevolucionRenta::query()
            ->whereIn('estatus', ['Pendiente', 'PendienteCaja'])
            ->where('cliente_id', $this->cliente_id)
            ->when($this->direccion_entrega_id, fn ($builder) => $builder->where('direccion_entrega_id', $this->direccion_entrega_id))
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
        return $this->items->groupBy(function ($item) {
            $direccion = $item->notaVentaRenta?->direccionEntrega;
            return $direccion ? $direccion->id : 0;
        })->map(function (Collection $itemsGrupo) {
            return $itemsGrupo->groupBy(fn ($item) => $item->producto_id ?? $item->producto?->descripcion ?? 'sin-producto')
                ->map(function (Collection $itemsProducto) {
                    $item = clone $itemsProducto->first();
                    $item->cantidad = $itemsProducto->sum('cantidad');
                    $item->cantidad_devuelta = $itemsProducto->sum('cantidad_devuelta');
                    $item->importe_renta = $itemsProducto->sum('importe_renta');

                    return $item;
                })->values();
        });
    }

    #[Computed]
    public function totalImporteRenta(): float
    {
        return $this->items->sum('importe_renta');
    }

    #[Computed]
    public function totalPrecioVenta(): float
    {
        return $this->items->sum(function ($item) {
            return ($item->producto?->precio_venta ?? 0) * $item->cantidad;
        });
    }

    public function exportPdf()
    {
        if (!$this->cliente_id) {
            $this->dispatch('notify', type: 'warning', message: 'Seleccione un cliente primero.');
            return;
        }

        $items = $this->items;
        $itemsAgrupados = $this->itemsAgrupados;
        $cliente = Clientes::find($this->cliente_id);
        $totalImporteRenta = $this->totalImporteRenta;
        $totalPrecioVenta = $this->totalPrecioVenta;

        $pdf = Pdf::loadView('exports.items-rentados-por-direccion-pdf', compact(
            'items', 'itemsAgrupados', 'cliente', 'totalImporteRenta', 'totalPrecioVenta'
        ))->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'items_rentados_' . $cliente->nombre . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        if (!$this->cliente_id) {
            $this->dispatch('notify', type: 'warning', message: 'Seleccione un cliente primero.');
            return;
        }

        $items = $this->items;
        $cliente = Clientes::find($this->cliente_id);

        $csvData = [];
        $csvData[] = ['Dirección de Entrega', 'Producto', 'Clave', 'Cantidad Enviada', 'Devueltos', 'Pendientes', 'Importe Renta', 'Precio Venta Unit.', 'Total Precio Venta'];

        foreach ($this->itemsAgrupados as $itemsGrupo) {
            $direccion = $itemsGrupo->first()->notaVentaRenta?->direccionEntrega;
            $direccionNombre = $direccion ? $direccion->nombre_direccion . ' - ' . $direccion->direccion_completa : $itemsGrupo->first()->cliente_direccion;
            $subtotalRenta = $itemsGrupo->sum('importe_renta');
            $subtotalVenta = $itemsGrupo->sum(fn ($item) => ($item->producto?->precio_venta ?? 0) * $item->cantidad);
            $cantidad = $itemsGrupo->sum('cantidad');
            $devueltos = $itemsGrupo->sum('cantidad_devuelta');

            foreach ($itemsGrupo as $item) {
                $precioVenta = $item->producto?->precio_venta ?? 0;
                $cantidadItem = (float) $item->cantidad;
                $devueltosItem = (float) $item->cantidad_devuelta;

                $csvData[] = [
                    $direccionNombre,
                    $item->producto?->descripcion ?? 'N/A',
                    $item->producto?->clave ?? 'N/A',
                    $cantidadItem,
                    $devueltosItem,
                    max(0, $cantidadItem - $devueltosItem),
                    number_format($item->importe_renta, 2),
                    number_format($precioVenta, 2),
                    number_format($precioVenta * $cantidadItem, 2),
                ];
            }

            $csvData[] = [
                $direccionNombre,
                'Subtotal dirección',
                '',
                $cantidad,
                $devueltos,
                max(0, $cantidad - $devueltos),
                number_format($subtotalRenta, 2),
                '',
                number_format($subtotalVenta, 2),
            ];
        }

        $csvData[] = [];
        $csvData[] = ['', '', '', '', '', '', 'TOTALES:', number_format($this->totalImporteRenta, 2), number_format($this->totalPrecioVenta, 2)];

        $filename = 'items_rentados_' . $cliente->nombre . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
