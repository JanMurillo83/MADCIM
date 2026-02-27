<?php

namespace App\Filament\Pages\Reportes;

use App\Models\Clientes;
use App\Models\FacturasCfdi;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Sucursal;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class VentasPorPeriodo extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Ventas y Rentas por Periodo';
    protected static ?string $title = 'Ventas y Rentas por Periodo';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reportes.ventas-por-periodo';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $cliente_id = null;
    public ?string $estatus = null;
    public ?string $tipo = 'consolidado';
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

    public function getEstatusesProperty(): array
    {
        return [
            'Activa' => 'Activa',
            'Pagada' => 'Pagada',
            'Cancelada' => 'Cancelada',
            'borrador' => 'Borrador',
        ];
    }

    public function getTiposProperty(): array
    {
        return [
            'ventas' => 'Ventas',
            'rentas' => 'Rentas',
            'consolidado' => 'Consolidado',
        ];
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
    public function ventas(): Collection
    {
        $ventasNotas = collect();
        if ($this->tipo === 'ventas' || $this->tipo === 'consolidado') {
            $ventasNotas = NotasVentaVenta::query()
            ->with('cliente')
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->when($this->sucursal_id, fn ($q) => $q->where('sucursal_id', $this->sucursal_id))
            ->when($this->usuario_id, fn ($q) => $q->where('user_id', $this->usuario_id))
            ->when($this->estatus, fn ($q) => $q->where('estatus', $this->estatus))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin))
            ->get()
            ->map(function ($row) {
                return [
                    'tipo' => 'Nota de Venta',
                    'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                    'fecha_emision' => optional($row->fecha_emision)->format('Y-m-d'),
                    'cliente' => $row->cliente?->nombre ?? 'N/A',
                    'subtotal' => (float) $row->subtotal,
                    'impuestos_total' => (float) $row->impuestos_total,
                    'total' => (float) $row->total,
                    'saldo_pendiente' => (float) $row->saldo_pendiente,
                    'estatus' => $row->estatus,
                ];
            });
        }

        $ventasFacturas = collect();
        if ($this->tipo === 'ventas' || $this->tipo === 'consolidado') {
            $ventasFacturas = FacturasCfdi::query()
            ->with('cliente')
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->when($this->sucursal_id, fn ($q) => $q->where('sucursal_id', $this->sucursal_id))
            ->when($this->usuario_id, fn ($q) => $q->where('user_id', $this->usuario_id))
            ->when($this->estatus, fn ($q) => $q->where('estatus', $this->estatus))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin))
            ->get()
            ->map(function ($row) {
                return [
                    'tipo' => 'Factura CFDI',
                    'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                    'fecha_emision' => optional($row->fecha_emision)->format('Y-m-d'),
                    'cliente' => $row->cliente?->nombre ?? 'N/A',
                    'subtotal' => (float) $row->subtotal,
                    'impuestos_total' => (float) $row->impuestos_total,
                    'total' => (float) $row->total,
                    'saldo_pendiente' => (float) $row->saldo_pendiente,
                    'estatus' => $row->estatus,
                ];
            });
        }

        $rentas = collect();
        if ($this->tipo === 'rentas' || $this->tipo === 'consolidado') {
            $rentas = NotasVentaRenta::query()
                ->with('cliente')
                ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
                ->when($this->sucursal_id, fn ($q) => $q->where('sucursal_id', $this->sucursal_id))
                ->when($this->usuario_id, fn ($q) => $q->where('user_id', $this->usuario_id))
                ->when($this->estatus, fn ($q) => $q->where('estatus', $this->estatus))
                ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
                ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin))
                ->get()
                ->map(function ($row) {
                    return [
                        'tipo' => 'Renta',
                        'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                        'fecha_emision' => optional($row->fecha_emision)->format('Y-m-d'),
                        'cliente' => $row->cliente?->nombre ?? 'N/A',
                        'subtotal' => (float) $row->subtotal,
                        'impuestos_total' => (float) $row->impuestos_total,
                        'total' => (float) $row->total,
                        'saldo_pendiente' => (float) $row->saldo_pendiente,
                        'estatus' => $row->estatus,
                    ];
                });
        }

        return $ventasNotas
            ->concat($ventasFacturas)
            ->concat($rentas)
            ->sortBy('fecha_emision')
            ->values();
    }

    #[Computed]
    public function totalSubtotal(): float
    {
        return (float) $this->ventas->sum('subtotal');
    }

    #[Computed]
    public function totalImpuestos(): float
    {
        return (float) $this->ventas->sum('impuestos_total');
    }

    #[Computed]
    public function totalGeneral(): float
    {
        return (float) $this->ventas->sum('total');
    }

    public function exportPdf()
    {
        $ventas = $this->ventas;
        $totals = [
            'subtotal' => $this->totalSubtotal,
            'impuestos' => $this->totalImpuestos,
            'total' => $this->totalGeneral,
        ];

        $pdf = Pdf::loadView('exports.reportes.ventas-por-periodo-pdf', [
            'ventas' => $ventas,
            'totals' => $totals,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'tipo' => $this->tipo,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'ventas_por_periodo_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Tipo', 'Serie/Folio', 'Fecha', 'Cliente', 'Subtotal', 'Impuestos', 'Total', 'Saldo pendiente', 'Estatus'];

        foreach ($this->ventas as $venta) {
            $rows[] = [
                $venta['tipo'],
                $venta['serie_folio'],
                $venta['fecha_emision'],
                $venta['cliente'],
                number_format($venta['subtotal'], 2, '.', ''),
                number_format($venta['impuestos_total'], 2, '.', ''),
                number_format($venta['total'], 2, '.', ''),
                number_format($venta['saldo_pendiente'], 2, '.', ''),
                $venta['estatus'],
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', '', 'TOTALES', number_format($this->totalSubtotal, 2, '.', ''), number_format($this->totalImpuestos, 2, '.', ''), number_format($this->totalGeneral, 2, '.', ''), '', ''];

        $filename = 'ventas_por_periodo_' . now()->format('Ymd_His') . '.csv';

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
