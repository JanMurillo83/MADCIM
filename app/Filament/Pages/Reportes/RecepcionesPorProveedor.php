<?php

namespace App\Filament\Pages\Reportes;

use App\Models\Proveedores;
use App\Models\RecepcionCompra;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use App\Filament\Concerns\HasRolePageAccess;

class RecepcionesPorProveedor extends Page
{
    use HasRolePageAccess;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Recepciones por Proveedor';
    protected static ?string $title = 'Recepciones por Proveedor';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.reportes.recepciones-por-proveedor';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $proveedor_id = null;

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

    #[Computed]
    public function recepciones(): Collection
    {
        return RecepcionCompra::query()
            ->with('proveedor')
            ->when($this->proveedor_id, fn ($q) => $q->where('proveedor_id', $this->proveedor_id))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin))
            ->orderBy('fecha_emision')
            ->get();
    }

    #[Computed]
    public function totalRecepciones(): int
    {
        return $this->recepciones->count();
    }

    #[Computed]
    public function totalMonto(): float
    {
        return (float) $this->recepciones->sum('total');
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('exports.reportes.recepciones-por-proveedor-pdf', [
            'recepciones' => $this->recepciones,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'recepciones_por_proveedor_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Serie/Folio', 'Fecha', 'Proveedor', 'Subtotal', 'Total', 'Estatus'];

        foreach ($this->recepciones as $recepcion) {
            $rows[] = [
                trim(($recepcion->serie ?? '') . ($recepcion->folio ?? '')),
                optional($recepcion->fecha_emision)->format('Y-m-d'),
                $recepcion->proveedor?->nombre ?? 'N/A',
                number_format($recepcion->subtotal, 2, '.', ''),
                number_format($recepcion->total, 2, '.', ''),
                $recepcion->estatus,
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', 'TOTALES', '', number_format($this->totalMonto, 2, '.', ''), ''];

        $filename = 'recepciones_por_proveedor_' . now()->format('Ymd_His') . '.csv';

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
