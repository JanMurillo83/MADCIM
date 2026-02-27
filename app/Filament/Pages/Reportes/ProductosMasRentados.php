<?php

namespace App\Filament\Pages\Reportes;

use App\Models\Clientes;
use App\Models\Productos;
use App\Models\RegistroRenta;
use App\Models\Sucursal;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class ProductosMasRentados extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationLabel = 'Productos Mas Rentados';
    protected static ?string $title = 'Productos Mas Rentados';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.reportes.productos-mas-rentados';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $cliente_id = null;
    public ?int $producto_id = null;
    public ?int $sucursal_id = null;
    public ?int $usuario_id = null;

    public function mount(): void
    {
        $this->fecha_inicio = now()->startOfMonth()->toDateString();
        $this->fecha_fin = now()->toDateString();
    }

    public function getClientesProperty(): Collection
    {
        return Clientes::orderBy('nombre')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->nombre]);
    }

    public function getProductosProperty(): Collection
    {
        return Productos::orderBy('descripcion')
            ->get()
            ->mapWithKeys(fn ($p) => [$p->id => $p->clave . ' - ' . $p->descripcion]);
    }

    public function getSucursalesProperty(): Collection
    {
        return Sucursal::orderBy('nombre')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => $s->nombre]);
    }

    public function getUsuariosProperty(): Collection
    {
        return User::orderBy('name')
            ->get()
            ->mapWithKeys(fn ($u) => [$u->id => $u->name]);
    }

    #[Computed]
    public function productos(): Collection
    {
        $registros = RegistroRenta::query()
            ->with(['producto', 'cliente'])
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->when($this->producto_id, fn ($q) => $q->where('producto_id', $this->producto_id))
            ->when($this->sucursal_id, fn ($q) => $q->whereHas('notaVentaRenta', fn ($q2) => $q2->where('sucursal_id', $this->sucursal_id)))
            ->when($this->usuario_id, fn ($q) => $q->whereHas('notaVentaRenta', fn ($q2) => $q2->where('user_id', $this->usuario_id)))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_renta', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_renta', '<=', $this->fecha_fin))
            ->get();

        return $registros->groupBy('producto_id')->map(function ($items) {
            $producto = $items->first()?->producto;

            return [
                'clave' => $producto?->clave ?? 'N/A',
                'descripcion' => $producto?->descripcion ?? 'Producto eliminado',
                'cantidad' => (float) $items->sum('cantidad'),
                'importe_renta' => (float) $items->sum('importe_renta'),
                'importe_deposito' => (float) $items->sum('importe_deposito'),
            ];
        })->values()->sortByDesc('cantidad')->values();
    }

    #[Computed]
    public function totalCantidad(): float
    {
        return (float) $this->productos->sum('cantidad');
    }

    #[Computed]
    public function totalImporteRenta(): float
    {
        return (float) $this->productos->sum('importe_renta');
    }

    #[Computed]
    public function totalImporteDeposito(): float
    {
        return (float) $this->productos->sum('importe_deposito');
    }

    public function exportPdf()
    {
        $productos = $this->productos;
        $totals = [
            'cantidad' => $this->totalCantidad,
            'importe_renta' => $this->totalImporteRenta,
            'importe_deposito' => $this->totalImporteDeposito,
        ];

        $pdf = Pdf::loadView('exports.reportes.productos-mas-rentados-pdf', [
            'productos' => $productos,
            'totals' => $totals,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'productos_mas_rentados_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Clave', 'Producto', 'Cantidad', 'Importe renta', 'Importe deposito'];

        foreach ($this->productos as $row) {
            $rows[] = [
                $row['clave'],
                $row['descripcion'],
                number_format($row['cantidad'], 2, '.', ''),
                number_format($row['importe_renta'], 2, '.', ''),
                number_format($row['importe_deposito'], 2, '.', ''),
            ];
        }

        $rows[] = [];
        $rows[] = ['', 'TOTALES', number_format($this->totalCantidad, 2, '.', ''), number_format($this->totalImporteRenta, 2, '.', ''), number_format($this->totalImporteDeposito, 2, '.', '')];

        $filename = 'productos_mas_rentados_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
