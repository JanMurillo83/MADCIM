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

class ControlDepositos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel = 'Control de Depositos';
    protected static ?string $title = 'Control de Depositos';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.reportes.control-depositos';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $cliente_id = null;
    public ?string $estatus = null;
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
    public function depositos(): Collection
    {
        return NotasVentaRenta::query()
            ->with(['cliente', 'registrosRenta'])
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->when($this->sucursal_id, fn ($q) => $q->where('sucursal_id', $this->sucursal_id))
            ->when($this->usuario_id, fn ($q) => $q->where('user_id', $this->usuario_id))
            ->when($this->estatus, fn ($q) => $q->where('estatus', $this->estatus))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin))
            ->get()
            ->map(function ($row) {
                $depositoRegistros = (float) $row->registrosRenta->sum('importe_deposito');
                $depositoNota = (float) $row->deposito;
                return [
                    'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                    'fecha_emision' => optional($row->fecha_emision)->format('Y-m-d'),
                    'cliente' => $row->cliente?->nombre ?? 'N/A',
                    'deposito_nota' => $depositoNota,
                    'deposito_registros' => $depositoRegistros,
                    'diferencia' => $depositoNota - $depositoRegistros,
                    'estatus' => $row->estatus,
                ];
            });
    }

    #[Computed]
    public function totalDepositoNota(): float
    {
        return (float) $this->depositos->sum('deposito_nota');
    }

    #[Computed]
    public function totalDepositoRegistros(): float
    {
        return (float) $this->depositos->sum('deposito_registros');
    }

    #[Computed]
    public function totalDiferencia(): float
    {
        return (float) $this->depositos->sum('diferencia');
    }

    public function exportPdf()
    {
        $depositos = $this->depositos;
        $totals = [
            'nota' => $this->totalDepositoNota,
            'registros' => $this->totalDepositoRegistros,
            'diferencia' => $this->totalDiferencia,
        ];

        $pdf = Pdf::loadView('exports.reportes.control-depositos-pdf', [
            'depositos' => $depositos,
            'totals' => $totals,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'control_depositos_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Serie/Folio', 'Fecha', 'Cliente', 'Deposito nota', 'Deposito registros', 'Diferencia', 'Estatus'];

        foreach ($this->depositos as $row) {
            $rows[] = [
                $row['serie_folio'],
                $row['fecha_emision'],
                $row['cliente'],
                number_format($row['deposito_nota'], 2, '.', ''),
                number_format($row['deposito_registros'], 2, '.', ''),
                number_format($row['diferencia'], 2, '.', ''),
                $row['estatus'],
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', 'TOTALES', number_format($this->totalDepositoNota, 2, '.', ''), number_format($this->totalDepositoRegistros, 2, '.', ''), number_format($this->totalDiferencia, 2, '.', ''), ''];

        $filename = 'control_depositos_' . now()->format('Ymd_His') . '.csv';

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
