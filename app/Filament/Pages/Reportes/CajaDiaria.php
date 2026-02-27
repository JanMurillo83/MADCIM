<?php

namespace App\Filament\Pages\Reportes;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\Sucursal;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class CajaDiaria extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Caja Diaria';
    protected static ?string $title = 'Caja Diaria';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.reportes.caja-diaria';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $caja_id = null;
    public ?int $usuario_id = null;
    public ?int $sucursal_id = null;

    public function mount(): void
    {
        $this->fecha_inicio = now()->startOfMonth()->toDateString();
        $this->fecha_fin = now()->toDateString();
    }

    public function getCajasProperty(): Collection
    {
        return Caja::orderBy('nombre')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->nombre ?? ('Caja ' . $c->id)]);
    }

    public function getUsuariosProperty(): Collection
    {
        return User::orderBy('name')
            ->get()
            ->mapWithKeys(fn ($u) => [$u->id => $u->name]);
    }

    public function getSucursalesProperty(): Collection
    {
        return Sucursal::orderBy('nombre')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => $s->nombre]);
    }

    #[Computed]
    public function resumen(): Collection
    {
        $query = CajaMovimiento::query()
            ->with(['caja', 'user'])
            ->when($this->caja_id, fn ($q) => $q->where('caja_id', $this->caja_id))
            ->when($this->usuario_id, fn ($q) => $q->where('user_id', $this->usuario_id))
            ->when($this->sucursal_id, fn ($q) => $q->whereHas('caja', fn ($q2) => $q2->where('sucursal_id', $this->sucursal_id)))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha', '<=', $this->fecha_fin));

        $movs = $query->get();

        return $movs->groupBy(function ($m) {
            $fecha = optional($m->fecha)->format('Y-m-d');
            return $fecha . '|' . ($m->caja_id ?? 0);
        })->map(function ($items) {
            $first = $items->first();
            $fecha = optional($first->fecha)->format('Y-m-d');
            $cajaNombre = $first->caja?->nombre ?? ('Caja ' . ($first->caja_id ?? ''));
            $ingresos = $items->where('tipo', 'Ingreso')->sum('importe');
            $egresos = $items->where('tipo', 'Egreso')->sum('importe');
            $neto = $ingresos - $egresos;
            $usuario = $this->usuario_id ? ($first->user?->name ?? 'N/A') : 'Varios';

            return [
                'fecha' => $fecha,
                'caja' => $cajaNombre,
                'usuario' => $usuario,
                'ingresos' => (float) $ingresos,
                'egresos' => (float) $egresos,
                'neto' => (float) $neto,
                'movimientos' => $items->count(),
            ];
        })->values()->sortBy('fecha')->values();
    }

    #[Computed]
    public function totalIngresos(): float
    {
        return (float) $this->resumen->sum('ingresos');
    }

    #[Computed]
    public function totalEgresos(): float
    {
        return (float) $this->resumen->sum('egresos');
    }

    #[Computed]
    public function totalNeto(): float
    {
        return (float) $this->resumen->sum('neto');
    }

    public function exportPdf()
    {
        $resumen = $this->resumen;
        $totals = [
            'ingresos' => $this->totalIngresos,
            'egresos' => $this->totalEgresos,
            'neto' => $this->totalNeto,
        ];

        $pdf = Pdf::loadView('exports.reportes.caja-diaria-pdf', [
            'resumen' => $resumen,
            'totals' => $totals,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'caja_diaria_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Fecha', 'Caja', 'Usuario', 'Ingresos', 'Egresos', 'Neto', 'Movimientos'];

        foreach ($this->resumen as $row) {
            $rows[] = [
                $row['fecha'],
                $row['caja'],
                $row['usuario'],
                number_format($row['ingresos'], 2, '.', ''),
                number_format($row['egresos'], 2, '.', ''),
                number_format($row['neto'], 2, '.', ''),
                $row['movimientos'],
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', 'TOTALES', number_format($this->totalIngresos, 2, '.', ''), number_format($this->totalEgresos, 2, '.', ''), number_format($this->totalNeto, 2, '.', ''), ''];

        $filename = 'caja_diaria_' . now()->format('Ymd_His') . '.csv';

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
