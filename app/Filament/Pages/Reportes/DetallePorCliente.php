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
use App\Filament\Concerns\HasRolePageAccess;

class DetallePorCliente extends Page
{
    use HasRolePageAccess;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Detalle por Cliente';
    protected static ?string $title = 'Detalle por Cliente';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.reportes.detalle-por-cliente';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $cliente_id = null;
    public ?string $estatus = null;
    public ?string $tipo = null;
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
            'notas_venta_renta' => 'Notas de Renta',
            'notas_venta_venta' => 'Notas de Venta',
            'facturas_cfdi' => 'Facturas CFDI',
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
    public function movimientos(): Collection
    {
        $movs = collect();

        if (!$this->tipo || $this->tipo === 'notas_venta_renta') {
            $movs = $movs->concat(
                NotasVentaRenta::query()
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
                            'cliente' => $row->cliente?->nombre ?? 'N/A',
                            'tipo' => 'Nota de Renta',
                            'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                            'fecha' => optional($row->fecha_emision)->format('Y-m-d'),
                            'total' => (float) $row->total,
                            'saldo_pendiente' => (float) $row->saldo_pendiente,
                            'estatus' => $row->estatus,
                        ];
                    })
            );
        }

        if (!$this->tipo || $this->tipo === 'notas_venta_venta') {
            $movs = $movs->concat(
                NotasVentaVenta::query()
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
                            'cliente' => $row->cliente?->nombre ?? 'N/A',
                            'tipo' => 'Nota de Venta',
                            'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                            'fecha' => optional($row->fecha_emision)->format('Y-m-d'),
                            'total' => (float) $row->total,
                            'saldo_pendiente' => (float) $row->saldo_pendiente,
                            'estatus' => $row->estatus,
                        ];
                    })
            );
        }

        if (!$this->tipo || $this->tipo === 'facturas_cfdi') {
            $movs = $movs->concat(
                FacturasCfdi::query()
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
                            'cliente' => $row->cliente?->nombre ?? 'N/A',
                            'tipo' => 'Factura CFDI',
                            'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                            'fecha' => optional($row->fecha_emision)->format('Y-m-d'),
                            'total' => (float) $row->total,
                            'saldo_pendiente' => (float) $row->saldo_pendiente,
                            'estatus' => $row->estatus,
                        ];
                    })
            );
        }

        return $movs->sortBy(fn ($row) => ($row['cliente'] ?? '') . '|' . ($row['fecha'] ?? ''))->values();
    }

    #[Computed]
    public function totalImporte(): float
    {
        return (float) $this->movimientos->sum('total');
    }

    #[Computed]
    public function totalSaldo(): float
    {
        return (float) $this->movimientos->sum('saldo_pendiente');
    }

    #[Computed]
    public function totalDocumentos(): int
    {
        return $this->movimientos->count();
    }

    public function exportPdf()
    {
        $movimientos = $this->movimientos;
        $totals = [
            'total' => $this->totalImporte,
            'saldo' => $this->totalSaldo,
            'documentos' => $this->totalDocumentos,
        ];

        $pdf = Pdf::loadView('exports.reportes.detalle-por-cliente-pdf', [
            'movimientos' => $movimientos,
            'totals' => $totals,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'detalle_por_cliente_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Cliente', 'Tipo', 'Serie/Folio', 'Fecha', 'Total', 'Saldo pendiente', 'Estatus'];

        foreach ($this->movimientos as $row) {
            $rows[] = [
                $row['cliente'],
                $row['tipo'],
                $row['serie_folio'],
                $row['fecha'],
                number_format($row['total'], 2, '.', ''),
                number_format($row['saldo_pendiente'], 2, '.', ''),
                $row['estatus'],
            ];
        }

        $rows[] = [];
        $rows[] = ['TOTALES', '', '', '', number_format($this->totalImporte, 2, '.', ''), number_format($this->totalSaldo, 2, '.', ''), $this->totalDocumentos];

        $filename = 'detalle_por_cliente_' . now()->format('Ymd_His') . '.csv';

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
