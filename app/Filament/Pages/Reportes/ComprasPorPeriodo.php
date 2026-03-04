<?php

namespace App\Filament\Pages\Reportes;

use App\Models\OrdenCompra;
use App\Models\Proveedores;
use App\Models\RecepcionCompra;
use App\Models\RequisicionCompra;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use App\Filament\Concerns\HasRolePageAccess;

class ComprasPorPeriodo extends Page
{
    use HasRolePageAccess;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Compras por Periodo';
    protected static ?string $title = 'Compras por Periodo';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.reportes.compras-por-periodo';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $proveedor_id = null;
    public ?string $estatus = null;
    public ?string $tipo = 'consolidado';

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
            'Cerrada' => 'Cerrada',
            'Cancelada' => 'Cancelada',
        ];
    }

    public function getTiposProperty(): array
    {
        return [
            'requisiciones' => 'Requisiciones',
            'ordenes' => 'Ordenes',
            'recepciones' => 'Recepciones',
            'consolidado' => 'Consolidado',
        ];
    }

    #[Computed]
    public function compras(): Collection
    {
        $requisiciones = collect();
        if ($this->tipo === 'requisiciones' || $this->tipo === 'consolidado') {
            $requisiciones = RequisicionCompra::query()
                ->with('proveedor')
                ->when($this->proveedor_id, fn ($q) => $q->where('proveedor_id', $this->proveedor_id))
                ->when($this->estatus, fn ($q) => $q->where('estatus', $this->estatus))
                ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
                ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin))
                ->get()
                ->map(function ($row) {
                    return [
                        'tipo' => 'Requisicion',
                        'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                        'fecha_emision' => optional($row->fecha_emision)->format('Y-m-d'),
                        'proveedor' => $row->proveedor?->nombre ?? 'N/A',
                        'subtotal' => (float) $row->subtotal,
                        'impuestos_total' => (float) $row->impuestos_total,
                        'total' => (float) $row->total,
                        'estatus' => $row->estatus,
                    ];
                });
        }

        $ordenes = collect();
        if ($this->tipo === 'ordenes' || $this->tipo === 'consolidado') {
            $ordenes = OrdenCompra::query()
                ->with('proveedor')
                ->when($this->proveedor_id, fn ($q) => $q->where('proveedor_id', $this->proveedor_id))
                ->when($this->estatus, fn ($q) => $q->where('estatus', $this->estatus))
                ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
                ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin))
                ->get()
                ->map(function ($row) {
                    return [
                        'tipo' => 'Orden',
                        'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                        'fecha_emision' => optional($row->fecha_emision)->format('Y-m-d'),
                        'proveedor' => $row->proveedor?->nombre ?? 'N/A',
                        'subtotal' => (float) $row->subtotal,
                        'impuestos_total' => (float) $row->impuestos_total,
                        'total' => (float) $row->total,
                        'estatus' => $row->estatus,
                    ];
                });
        }

        $recepciones = collect();
        if ($this->tipo === 'recepciones' || $this->tipo === 'consolidado') {
            $recepciones = RecepcionCompra::query()
                ->with('proveedor')
                ->when($this->proveedor_id, fn ($q) => $q->where('proveedor_id', $this->proveedor_id))
                ->when($this->estatus, fn ($q) => $q->where('estatus', $this->estatus))
                ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
                ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin))
                ->get()
                ->map(function ($row) {
                    return [
                        'tipo' => 'Recepcion',
                        'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                        'fecha_emision' => optional($row->fecha_emision)->format('Y-m-d'),
                        'proveedor' => $row->proveedor?->nombre ?? 'N/A',
                        'subtotal' => (float) $row->subtotal,
                        'impuestos_total' => (float) $row->impuestos_total,
                        'total' => (float) $row->total,
                        'estatus' => $row->estatus,
                    ];
                });
        }

        return $requisiciones
            ->concat($ordenes)
            ->concat($recepciones)
            ->sortBy('fecha_emision')
            ->values();
    }

    #[Computed]
    public function totalSubtotal(): float
    {
        return (float) $this->compras->sum('subtotal');
    }

    #[Computed]
    public function totalImpuestos(): float
    {
        return (float) $this->compras->sum('impuestos_total');
    }

    #[Computed]
    public function totalGeneral(): float
    {
        return (float) $this->compras->sum('total');
    }

    public function exportPdf()
    {
        $compras = $this->compras;
        $totals = [
            'total' => $this->totalGeneral,
        ];

        $pdf = Pdf::loadView('exports.reportes.compras-por-periodo-pdf', [
            'compras' => $compras,
            'totals' => $totals,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'tipo' => $this->tipo,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'compras_por_periodo_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Tipo', 'Serie/Folio', 'Fecha', 'Proveedor', 'Total', 'Estatus'];

        foreach ($this->compras as $row) {
            $rows[] = [
                $row['tipo'],
                $row['serie_folio'],
                $row['fecha_emision'],
                $row['proveedor'],
                number_format($row['total'], 2, '.', ''),
                $row['estatus'],
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', '', 'TOTALES', number_format($this->totalGeneral, 2, '.', ''), ''];

        $filename = 'compras_por_periodo_' . now()->format('Ymd_His') . '.csv';

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
