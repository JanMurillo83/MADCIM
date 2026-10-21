<?php

namespace App\Filament\Clusters\RecursosHumanos\Pages;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Concerns\HasRolePageAccess;
use App\Filament\Concerns\RestringeSucursal;
use App\Models\Contrato;
use App\Models\DocumentoEmpleado;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class AlertasExpediente extends Page
{
    use HasRolePageAccess;
    use RestringeSucursal;

    protected string $view = 'filament.clusters.recursos-humanos.pages.alertas-expediente';

    protected static ?string $cluster = RecursosHumanosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Alertas de expediente';

    protected static ?string $title = 'Alertas de expediente laboral';

    #[Computed]
    public function contratos(): Collection
    {
        $hoy = today();
        $limite = $hoy->copy()->addDays(30);

        return Contrato::query()
            ->with('empleado')
            ->whereNotIn('estatus', ['cancelado'])
            ->whereNotNull('fecha_fin')
            ->whereDate('fecha_fin', '<=', $limite)
            ->orderBy('fecha_fin')
            ->get();
    }

    #[Computed]
    public function documentos(): Collection
    {
        $hoy = today();

        return DocumentoEmpleado::query()
            ->with('empleado')
            ->whereNotIn('estatus', ['cancelado'])
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<=', $hoy->copy()->addYear())
            ->orderBy('fecha_vencimiento')
            ->get()
            ->filter(fn (DocumentoEmpleado $documento): bool => $documento->fecha_vencimiento->lte(
                $hoy->copy()->addDays($documento->dias_alerta)
            ))
            ->values();
    }

    #[Computed]
    public function vencidos(): int
    {
        return $this->contratos->filter(fn (Contrato $contrato): bool => $contrato->fecha_fin->isPast() && ! $contrato->fecha_fin->isToday())->count()
            + $this->documentos->filter(fn (DocumentoEmpleado $documento): bool => $documento->fecha_vencimiento->isPast() && ! $documento->fecha_vencimiento->isToday())->count();
    }
}
