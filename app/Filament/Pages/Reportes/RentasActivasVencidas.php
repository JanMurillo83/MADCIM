<?php

namespace App\Filament\Pages\Reportes;

use App\Models\Clientes;
use App\Models\NotasVentaRenta;
use App\Models\Sucursal;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class RentasActivasVencidas extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Rentas Activas/Vencidas';
    protected static ?string $title = 'Rentas Activas y Vencidas';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.reportes.rentas-activas-vencidas';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $cliente_id = null;
    public ?string $estatus = null;
    public ?string $estado = null;
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
        ];
    }

    public function getEstadosProperty(): array
    {
        return [
            'Activa' => 'Activa',
            'Vencida' => 'Vencida',
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
    public function rentas(): Collection
    {
        $hoy = now()->toDateString();

        $query = NotasVentaRenta::query()
            ->with('cliente')
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->when($this->sucursal_id, fn ($q) => $q->where('sucursal_id', $this->sucursal_id))
            ->when($this->usuario_id, fn ($q) => $q->where('user_id', $this->usuario_id))
            ->when($this->estatus, fn ($q) => $q->where('estatus', $this->estatus))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin));

        if ($this->estado === 'Activa') {
            $query->whereIn('estatus', ['Activa', 'Pagada'])
                ->where(function ($q) use ($hoy) {
                    $q->whereNull('fecha_vencimiento')
                        ->orWhere('fecha_vencimiento', '>=', $hoy);
                });
        }

        if ($this->estado === 'Vencida') {
            $query->whereIn('estatus', ['Activa', 'Pagada'])
                ->whereNotNull('fecha_vencimiento')
                ->where('fecha_vencimiento', '<', $hoy);
        }

        return $query->get()->map(function ($row) use ($hoy) {
            $vencida = $row->fecha_vencimiento && $row->fecha_vencimiento->toDateString() < $hoy;
            return [
                'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                'fecha_emision' => optional($row->fecha_emision)->format('Y-m-d'),
                'fecha_vencimiento' => optional($row->fecha_vencimiento)->format('Y-m-d'),
                'cliente' => $row->cliente?->nombre ?? 'N/A',
                'total' => (float) $row->total,
                'saldo_pendiente' => (float) $row->saldo_pendiente,
                'estatus' => $row->estatus,
                'estado' => $vencida ? 'Vencida' : 'Activa',
            ];
        });
    }

    #[Computed]
    public function totalRentas(): float
    {
        return (float) $this->rentas->sum('total');
    }

    #[Computed]
    public function totalSaldo(): float
    {
        return (float) $this->rentas->sum('saldo_pendiente');
    }

    public function exportPdf()
    {
        $rentas = $this->rentas;
        $totals = [
            'total' => $this->totalRentas,
            'saldo' => $this->totalSaldo,
        ];

        $pdf = Pdf::loadView('exports.reportes.rentas-activas-vencidas-pdf', [
            'rentas' => $rentas,
            'totals' => $totals,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'rentas_activas_vencidas_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Serie/Folio', 'Fecha', 'Fecha vencimiento', 'Cliente', 'Total', 'Saldo', 'Estatus', 'Estado'];

        foreach ($this->rentas as $row) {
            $rows[] = [
                $row['serie_folio'],
                $row['fecha_emision'],
                $row['fecha_vencimiento'],
                $row['cliente'],
                number_format($row['total'], 2, '.', ''),
                number_format($row['saldo_pendiente'], 2, '.', ''),
                $row['estatus'],
                $row['estado'],
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', '', 'TOTALES', number_format($this->totalRentas, 2, '.', ''), number_format($this->totalSaldo, 2, '.', ''), '', ''];

        $filename = 'rentas_activas_vencidas_' . now()->format('Ymd_His') . '.csv';

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
