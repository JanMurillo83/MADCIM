<?php

namespace App\Filament\Pages\Reportes;

use App\Models\Clientes;
use App\Models\CajaMovimiento;
use App\Models\CierreDevolucionRenta;
use App\Models\DevolucionesRenta;
use App\Models\NotasVentaRenta;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use App\Services\CierreDevolucionRentaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use App\Filament\Concerns\HasRolePageAccess;
use App\Filament\Concerns\RestringeSucursal;

class ControlDepositos extends Page
{
    use HasRolePageAccess;
    use RestringeSucursal;
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
    public ?int $cierreVerificacionId = null;
    public string $folioIne = '';

    public function mount(): void
    {
        $this->inicializarSucursalUsuario();
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
        ];
    }

    #[Computed]
    public function cierresPendientes(): Collection
    {
        return CierreDevolucionRenta::query()
            ->with(['cliente', 'direccionEntrega'])
            ->whereIn('estatus', ['Pendiente', 'PendienteCaja'])
            ->when($this->cliente_id, fn ($query) => $query->where('cliente_id', $this->cliente_id))
            ->when($this->sucursalEfectiva(), fn ($query) => $query->where('sucursal_id', $this->sucursalEfectiva()))
            ->when($this->fecha_inicio, fn ($query) => $query->whereDate('created_at', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($query) => $query->whereDate('created_at', '<=', $this->fecha_fin))
            ->get();
    }

    public function abrirVerificacionDeposito(int $cierreId): void
    {
        $this->cierreVerificacionId = $cierreId;
        $this->folioIne = '';
    }

    public function cancelarVerificacionDeposito(): void
    {
        $this->cierreVerificacionId = null;
        $this->folioIne = '';
        $this->resetValidation('folioIne');
    }

    public function procesarDeposito(): void
    {
        try {
            $this->validate([
                'folioIne' => ['required', 'string', 'max:100'],
            ], [
                'folioIne.required' => 'Capture el folio de INE para continuar.',
            ]);

            if (!$this->cierreVerificacionId) {
                return;
            }

            $procesado = app(CierreDevolucionRentaService::class)->procesarDepositoPendiente(
                $this->cierreVerificacionId,
                Auth::id(),
                $this->folioIne,
            );

            $this->cancelarVerificacionDeposito();

            $notificacion = Notification::make()
                ->title($procesado ? 'Depósito procesado' : 'Caja no disponible')
                ->body($procesado ? 'El egreso fue registrado en caja.' : 'Abra una caja para registrar el egreso.');

            if ($procesado) {
                $notificacion->success();
            } else {
                $notificacion->warning();
            }

            $notificacion->send();
        } catch (\DomainException $exception) {
            Notification::make()
                ->danger()
                ->title('Folio de INE incorrecto')
                ->body($exception->getMessage())
                ->send();
        } catch (\Throwable $exception) {
            report($exception);
            Notification::make()
                ->danger()
                ->title('No se pudo procesar el depósito')
                ->body('Revise el cierre y vuelva a intentarlo.')
                ->send();
        }
    }

    public function getSucursalesProperty(): Collection
    {
        return $this->sucursalesDisponibles();
    }

    public function getUsuariosProperty(): Collection
    {
        return User::orderBy('name')
            ->get()
            ->mapWithKeys(fn ($u) => [$u->id => $u->name]);
    }

    public function actualizarReporte(): void
    {
        unset($this->depositos, $this->totalNota, $this->totalDeposito, $this->totalDepositoDevuelto, $this->totalDepositoPendiente);

        Notification::make()
            ->success()
            ->title('Reporte actualizado')
            ->send();
    }

    #[Computed]
    public function depositos(): Collection
    {
        $notas = NotasVentaRenta::query()
            ->with('sucursal')
            ->where('deposito', '>', 0)
            ->where('estatus', '!=', 'Cancelada')
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->when($this->sucursalEfectiva(), fn ($q) => $q->where('sucursal_id', $this->sucursalEfectiva()))
            ->when($this->usuario_id, fn ($q) => $q->where('user_id', $this->usuario_id))
            ->when($this->estatus, fn ($q) => $q->where('estatus', $this->estatus))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_emision', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_emision', '<=', $this->fecha_fin))
            ->get();
        $devueltosPorNota = $this->depositosDevueltosPorNota($notas);

        return $notas
            ->map(function ($row) use ($devueltosPorNota) {
                $deposito = (float) $row->deposito;
                $depositoDevuelto = min($deposito, (float) ($devueltosPorNota[$row->id] ?? 0));
                return [
                    'serie_folio' => trim(($row->serie ?? '') . ($row->folio ?? '')),
                    'sucursal' => $row->sucursal?->nombre ?? 'N/A',
                    'fecha_emision' => optional($row->fecha_emision)->format('Y-m-d'),
                    'total_nota' => (float) $row->total,
                    'deposito' => $deposito,
                    'deposito_devuelto' => $depositoDevuelto,
                    'deposito_pendiente' => max(0, $deposito - $depositoDevuelto),
                    'estado_pago' => (float) $row->saldo_pendiente <= 0
                        ? 'Pagada'
                        : 'Pendiente de pago',
                ];
            });
    }

    private function depositosDevueltosPorNota(Collection $notas): Collection
    {
        $notaIds = $notas->pluck('id')->map(fn ($id): int => (int) $id);
        $devueltos = $notaIds->mapWithKeys(fn (int $id): array => [$id => 0.0]);

        if ($notaIds->isEmpty()) {
            return $devueltos;
        }

        $movimientos = CajaMovimiento::query()
            ->where('tipo', 'Egreso')
            ->where('fuente', 'Devolución depósito renta')
            ->whereIn('movimentable_type', [
                NotasVentaRenta::class,
                DevolucionesRenta::class,
                CierreDevolucionRenta::class,
            ])
            ->get(['importe', 'movimentable_type', 'movimentable_id']);

        $devolucionIds = $movimientos
            ->where('movimentable_type', DevolucionesRenta::class)
            ->pluck('movimentable_id');
        $devoluciones = DevolucionesRenta::withoutGlobalScope('sucursal')
            ->whereIn('id', $devolucionIds)
            ->pluck('documento_origen_id', 'id');

        $cierreIds = $movimientos
            ->where('movimentable_type', CierreDevolucionRenta::class)
            ->pluck('movimentable_id');
        $cierres = CierreDevolucionRenta::withoutGlobalScope('sucursal')
            ->whereIn('id', $cierreIds)
            ->get(['id', 'nota_ids']);
        $depositosPorNota = NotasVentaRenta::withoutGlobalScope('sucursal')
            ->whereIn('id', $cierres->flatMap(fn (CierreDevolucionRenta $cierre) => $cierre->nota_ids ?? [])->unique())
            ->pluck('deposito', 'id')
            ->map(fn ($deposito): float => (float) $deposito);

        foreach ($movimientos as $movimiento) {
            $importe = (float) $movimiento->importe;

            if ($movimiento->movimentable_type === NotasVentaRenta::class) {
                if ($devueltos->has((int) $movimiento->movimentable_id)) {
                    $devueltos->put((int) $movimiento->movimentable_id, $devueltos[(int) $movimiento->movimentable_id] + $importe);
                }
                continue;
            }

            if ($movimiento->movimentable_type === DevolucionesRenta::class) {
                $notaId = (int) ($devoluciones[$movimiento->movimentable_id] ?? 0);
                if ($devueltos->has($notaId)) {
                    $devueltos->put($notaId, $devueltos[$notaId] + $importe);
                }
                continue;
            }

            $cierre = $cierres->firstWhere('id', $movimiento->movimentable_id);
            $idsCierre = collect($cierre?->nota_ids ?? [])->map(fn ($id): int => (int) $id);
            $depositoTotal = (float) $idsCierre->sum(fn (int $id): float => $depositosPorNota[$id] ?? 0);

            if ($depositoTotal <= 0) {
                continue;
            }

            foreach ($idsCierre as $notaId) {
                if ($devueltos->has($notaId)) {
                    $proporcion = ($depositosPorNota[$notaId] ?? 0) / $depositoTotal;
                    $devueltos->put($notaId, $devueltos[$notaId] + ($importe * $proporcion));
                }
            }
        }

        return $devueltos;
    }

    #[Computed]
    public function totalNota(): float
    {
        return (float) $this->depositos->sum('total_nota');
    }

    #[Computed]
    public function totalDeposito(): float
    {
        return (float) $this->depositos->sum('deposito');
    }

    #[Computed]
    public function totalDepositoDevuelto(): float
    {
        return (float) $this->depositos->sum('deposito_devuelto');
    }

    #[Computed]
    public function totalDepositoPendiente(): float
    {
        return (float) $this->depositos->sum('deposito_pendiente');
    }

    public function exportPdf()
    {
        $depositos = $this->depositos;
        $totals = [
            'total_nota' => $this->totalNota,
            'deposito' => $this->totalDeposito,
            'devuelto' => $this->totalDepositoDevuelto,
            'pendiente' => $this->totalDepositoPendiente,
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
        $rows[] = ['Nota', 'Sucursal', 'Fecha', 'Total nota', 'Deposito', 'Devuelto', 'Pendiente', 'Estado de pago'];

        foreach ($this->depositos as $row) {
            $rows[] = [
                $row['serie_folio'],
                $row['sucursal'],
                $row['fecha_emision'],
                number_format($row['total_nota'], 2, '.', ''),
                number_format($row['deposito'], 2, '.', ''),
                number_format($row['deposito_devuelto'], 2, '.', ''),
                number_format($row['deposito_pendiente'], 2, '.', ''),
                $row['estado_pago'],
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', 'TOTALES', number_format($this->totalNota, 2, '.', ''), number_format($this->totalDeposito, 2, '.', ''), number_format($this->totalDepositoDevuelto, 2, '.', ''), number_format($this->totalDepositoPendiente, 2, '.', ''), ''];

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
