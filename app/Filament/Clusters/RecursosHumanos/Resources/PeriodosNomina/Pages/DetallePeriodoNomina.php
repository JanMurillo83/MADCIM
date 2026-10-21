<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\PeriodosNominaResource;
use App\Filament\Concerns\HasRolePageAccess;
use App\Models\NominaSemanal;
use App\Models\PeriodoNomina;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class DetallePeriodoNomina extends Page
{
    use HasRolePageAccess;

    protected static string $resource = PeriodosNominaResource::class;

    protected string $view = 'filament.clusters.recursos-humanos.resources.periodos-nomina.pages.detalle-periodo-nomina';

    public PeriodoNomina $record;

    public function mount(int|string $record): void
    {
        $this->record = PeriodoNomina::query()->findOrFail($record);
    }

    public function getTitle(): string
    {
        return "Nómina {$this->record->fecha_inicio->format('d/m/Y')} - {$this->record->fecha_fin->format('d/m/Y')}";
    }

    #[Computed]
    public function nominas(): Collection
    {
        return NominaSemanal::query()
            ->with('empleado')
            ->where('periodo_nomina_id', $this->record->id)
            ->orderBy('empleado_id')
            ->get();
    }

    #[Computed]
    public function totales(): array
    {
        return [
            'empleados' => $this->nominas->count(),
            'faltas' => $this->nominas->sum('faltas'),
            'minutos_retardo' => $this->nominas->sum('minutos_retardo'),
            'horas_extra' => $this->nominas->sum('horas_extra'),
            'descuentos' => $this->nominas->sum('descuentos'),
            'total' => $this->nominas->sum('total'),
        ];
    }
}
