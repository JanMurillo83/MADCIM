<?php

namespace App\Filament\Pages\Reportes;

use App\Filament\Concerns\HasRolePageAccess;
use App\Filament\Concerns\RestringeSucursal;
use App\Models\Caja;
use App\Models\CajaCorte;
use App\Models\Sucursal;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class CortesCaja extends Page
{
    use HasRolePageAccess;
    use RestringeSucursal;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Cortes de Caja';
    protected static ?string $title = 'Cortes de Caja';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.reportes.cortes-caja';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $sucursal_id = null;
    public ?int $caja_id = null;

    public function mount(): void
    {
        $this->inicializarSucursalUsuario();
        $this->fecha_inicio = now()->startOfMonth()->toDateString();
        $this->fecha_fin = now()->toDateString();
    }

    public function updatedSucursal_id(): void
    {
        $this->caja_id = null;
    }

    public function getSucursalesProperty(): Collection
    {
        return $this->sucursalesDisponibles();
    }

    public function getCajasProperty(): Collection
    {
        return Caja::query()
            ->when($this->sucursalEfectiva(), fn (Builder $query) => $query->where('sucursal_id', $this->sucursalEfectiva()))
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn (Caja $caja): array => [$caja->id => $caja->nombre ?: 'Caja ' . $caja->id]);
    }

    #[Computed]
    public function cortes(): Collection
    {
        return CajaCorte::query()
            ->with(['caja', 'sucursal', 'usuarioCierre', 'movimientos'])
            ->whereBetween('fecha_cierre', [
                $this->fecha_inicio . ' 00:00:00',
                $this->fecha_fin . ' 23:59:59',
            ])
            ->when($this->sucursalEfectiva(), fn (Builder $query) => $query->where('sucursal_id', $this->sucursalEfectiva()))
            ->when($this->caja_id, fn (Builder $query) => $query->where('caja_id', $this->caja_id))
            ->orderByDesc('fecha_cierre')
            ->get();
    }

    #[Computed]
    public function totalIngresos(): float
    {
        return (float) $this->cortes->sum('total_ingresos_cash');
    }

    #[Computed]
    public function totalEgresos(): float
    {
        return (float) $this->cortes->sum('total_egresos_cash');
    }

    #[Computed]
    public function totalDiferencia(): float
    {
        return (float) $this->cortes->sum('total_diferencia');
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('exports.reportes.cortes-caja-pdf', [
            'cortes' => $this->cortes,
            'totals' => $this->totals(),
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print $pdf->output(),
            'cortes_de_caja_' . now()->format('Ymd_His') . '.pdf',
        );
    }

    public function exportExcel()
    {
        $rows = [['Fecha cierre', 'Sucursal', 'Caja', 'Usuario cierre', 'Ingresos efectivo', 'Egresos efectivo', 'Efectivo teorico', 'Efectivo contado', 'Diferencia']];
        foreach ($this->cortes as $corte) {
            $rows[] = [
                $corte->fecha_cierre?->format('Y-m-d H:i'),
                $corte->sucursal?->nombre ?? 'Global',
                $corte->caja?->nombre ?? 'Caja ' . $corte->caja_id,
                $corte->usuarioCierre?->name ?? 'N/A',
                number_format((float) $corte->total_ingresos_cash, 2, '.', ''),
                number_format((float) $corte->total_egresos_cash, 2, '.', ''),
                number_format((float) $corte->efectivo_teorico, 2, '.', ''),
                number_format((float) $corte->efectivo_contado, 2, '.', ''),
                number_format((float) $corte->total_diferencia, 2, '.', ''),
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', 'TOTALES', '', number_format($this->totalIngresos, 2, '.', ''), number_format($this->totalEgresos, 2, '.', ''), '', '', number_format($this->totalDiferencia, 2, '.', '')];

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'cortes_de_caja_' . now()->format('Ymd_His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** @return array{ingresos: float, egresos: float, diferencia: float} */
    private function totals(): array
    {
        return [
            'ingresos' => $this->totalIngresos,
            'egresos' => $this->totalEgresos,
            'diferencia' => $this->totalDiferencia,
        ];
    }
}
