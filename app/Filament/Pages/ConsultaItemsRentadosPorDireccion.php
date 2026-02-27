<?php

namespace App\Filament\Pages;

use App\Models\ClienteDireccionEntrega;
use App\Models\Clientes;
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
    protected static ?string $navigationLabel = 'Items Rentados por Dirección';
    protected static ?string $title = 'Items Rentados por Dirección de Entrega';
    protected static string|null|\UnitEnum $navigationGroup = 'Consultas';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.consulta-items-rentados-por-direccion';

    public ?int $cliente_id = null;
    public ?int $direccion_entrega_id = null;

    public function getClientesProperty(): Collection
    {
        return Clientes::orderBy('nombre')
            ->whereHas('notasVentaRenta', function ($q) {
                $q->whereHas('registrosRenta', fn ($q2) => $q2->where('estado', 'Activo'));
            })
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
            ->where('estado', 'Activo');

        if ($this->direccion_entrega_id) {
            $query->whereHas('notaVentaRenta', function ($q) {
                $q->where('direccion_entrega_id', $this->direccion_entrega_id);
            });
        }

        return $query->get();
    }

    #[Computed]
    public function itemsAgrupados(): Collection
    {
        return $this->items->groupBy(function ($item) {
            $direccion = $item->notaVentaRenta?->direccionEntrega;
            return $direccion ? $direccion->id : 0;
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
        $csvData[] = ['Dirección de Entrega', 'Producto', 'Clave', 'Cantidad', 'Días Renta', 'Importe Renta', 'Precio Venta Unit.', 'Total Precio Venta'];

        foreach ($items as $item) {
            $direccion = $item->notaVentaRenta?->direccionEntrega;
            $direccionNombre = $direccion ? $direccion->nombre_direccion . ' - ' . $direccion->direccion_completa : $item->cliente_direccion;
            $precioVenta = $item->producto?->precio_venta ?? 0;

            $csvData[] = [
                $direccionNombre,
                $item->producto?->descripcion ?? 'N/A',
                $item->producto?->clave ?? 'N/A',
                $item->cantidad,
                $item->dias_renta,
                number_format($item->importe_renta, 2),
                number_format($precioVenta, 2),
                number_format($precioVenta * $item->cantidad, 2),
            ];
        }

        $csvData[] = [];
        $csvData[] = ['', '', '', '', 'TOTALES:', number_format($this->totalImporteRenta, 2), '', number_format($this->totalPrecioVenta, 2)];

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
