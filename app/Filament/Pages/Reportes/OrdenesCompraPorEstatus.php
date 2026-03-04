<?php

namespace App\Filament\Pages\Reportes;

use App\Models\OrdenCompra;
use App\Models\Proveedores;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use App\Filament\Concerns\HasRolePageAccess;

class OrdenesCompraPorEstatus extends Page
{
    use HasRolePageAccess;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Ordenes por Estatus';
    protected static ?string $title = 'Ordenes de Compra por Estatus';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.reportes.ordenes-por-estatus';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $proveedor_id = null;
    public ?string $estatus = null;

    public function mount(): void
    {
        $this->fecha_inicio = now()->startOfMonth()->toDateString();
        $this->fecha_fin = now()->toDateString();
    }

    public function getProveedoresProperty(): Collection
    {
        return Proveedores::orderBy('nombre')
            ->get()
            ->mapWithKeys(fn ($p) => [$p->id => $p->nombre]);
    }

    public function getEstatusesProperty(): array
    {
        return [
            'Nueva' => 'Nueva',
            'Autorizada' => 'Autorizada',
            'Enlazada' => 'Enlazada',
            'Cancelada' => 'Cancelada',
        ];
    }

    #[Computed]
    public function ordenes(): Collection
    {
        return OrdenCompra::query()
            ->with('proveedor')
            ->when($this->proveedor_id, fn ($q) => $q->where('proveedor_id', $this->proveedor_id))
            ->when($this->estatus, fn ($q) => $q->where('estatus', $this->estatus))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin))
            ->orderBy('fecha_emision')
            ->get();
    }

    #[Computed]
    public function totalOrdenes(): int
    {
        return $this->ordenes->count();
    }

    #[Computed]
    public function totalMonto(): float
    {
        return (float) $this->ordenes->sum('total');
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('exports.reportes.ordenes-por-estatus-pdf', [
            'ordenes' => $this->ordenes,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'ordenes_por_estatus_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Serie/Folio', 'Fecha', 'Proveedor', 'Total', 'Estatus'];

        foreach ($this->ordenes as $orden) {
            $rows[] = [
                trim(($orden->serie ?? '') . ($orden->folio ?? '')),
                optional($orden->fecha_emision)->format('Y-m-d'),
                $orden->proveedor?->nombre ?? 'N/A',
                number_format($orden->total, 2, '.', ''),
                $orden->estatus,
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', 'TOTALES', number_format($this->totalMonto, 2, '.', ''), ''];

        $filename = 'ordenes_por_estatus_' . now()->format('Ymd_His') . '.csv';

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
