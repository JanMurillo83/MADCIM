<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Models\Clientes;
use App\Models\NotasVentaRenta;
use Barryvdh\DomPDF\Facade\Pdf;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class DetalleCliente extends Page
{
    use HasRolePageAccess;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Detalle del Cliente';
    protected static ?string $title = 'Detalle del Cliente';
    protected static string|null|\UnitEnum $navigationGroup = 'Consultas';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.detalle-cliente';

    public ?int $cliente_id = null;

    #[Computed]
    public function clientes(): Collection
    {
        return Clientes::query()
            ->whereHas('notasVentaRenta')
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn ($cliente) => [$cliente->id => $cliente->nombre]);
    }

    #[Computed]
    public function notasPorFecha(): Collection
    {
        if (!$this->cliente_id) {
            return collect();
        }

        $notas = NotasVentaRenta::query()
            ->with([
                'notasEnvio' => fn ($query) => $query->orderBy('fecha_emision')->orderBy('id'),
                'notasEnvio.partidas.producto',
            ])
            ->where('cliente_id', $this->cliente_id)
            ->orderBy('fecha_emision')
            ->orderBy('id')
            ->get();

        return $notas->groupBy(function ($nota) {
            return $nota->fecha_emision?->format('Y-m-d') ?? 'Sin fecha';
        }, preserveKeys: true);
    }

    public function exportPdf()
    {
        if (!$this->cliente_id) {
            $this->dispatch('notify', type: 'warning', message: 'Seleccione un cliente primero.');
            return;
        }

        $cliente = Clientes::find($this->cliente_id);
        $notasPorFecha = $this->notasPorFecha;

        if (!$cliente || $notasPorFecha->isEmpty()) {
            $this->dispatch('notify', type: 'warning', message: 'No hay información para exportar.');
            return;
        }

        $pdf = Pdf::loadView('exports.detalle-cliente-pdf', compact('cliente', 'notasPorFecha'))
            ->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'detalle_cliente_' . $cliente->nombre . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        if (!$this->cliente_id) {
            $this->dispatch('notify', type: 'warning', message: 'Seleccione un cliente primero.');
            return;
        }

        $cliente = Clientes::find($this->cliente_id);
        $notasPorFecha = $this->notasPorFecha;

        if (!$cliente || $notasPorFecha->isEmpty()) {
            $this->dispatch('notify', type: 'warning', message: 'No hay información para exportar.');
            return;
        }

        $csvData = [];
        $csvData[] = [
            'Cliente',
            'Fecha Nota Renta',
            'Nota de Renta',
            'Nota de Envío',
            'Fecha Envío',
            'Producto',
            'Cantidad Enviada',
            'Cantidad Devuelta',
            'Cantidad Pendiente',
        ];

        foreach ($notasPorFecha as $fechaKey => $notas) {
            foreach ($notas as $nota) {
                $fechaNota = $nota->fecha_emision?->format('d/m/Y') ?? 'Sin fecha';
                $notaLabel = trim(($nota->serie ?? '') . ($nota->serie ? '-' : '') . ($nota->folio ?? ''));

                if ($nota->notasEnvio->isEmpty()) {
                    $csvData[] = [
                        $cliente->nombre,
                        $fechaNota,
                        $notaLabel,
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    ];
                    continue;
                }

                foreach ($nota->notasEnvio as $envio) {
                    $envioLabel = trim(($envio->serie ?? '') . ($envio->serie ? '-' : '') . ($envio->folio ?? ''));
                    $fechaEnvio = $envio->fecha_emision?->format('d/m/Y') ?? '-';

                    if ($envio->partidas->isEmpty()) {
                        $csvData[] = [
                            $cliente->nombre,
                            $fechaNota,
                            $notaLabel,
                            $envioLabel,
                            $fechaEnvio,
                            '',
                            0,
                            0,
                            0,
                        ];
                        continue;
                    }

                    foreach ($envio->partidas as $item) {
                        $devuelta = (float)($item->cantidad_devuelta ?? 0);
                        $pendiente = (float)$item->cantidad - $devuelta;
                        $csvData[] = [
                            $cliente->nombre,
                            $fechaNota,
                            $notaLabel,
                            $envioLabel,
                            $fechaEnvio,
                            $item->descripcion ?? ($item->producto?->descripcion ?? 'Item'),
                            (float)$item->cantidad,
                            $devuelta,
                            $pendiente,
                        ];
                    }
                }
            }
        }

        $filename = 'detalle_cliente_' . $cliente->nombre . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
