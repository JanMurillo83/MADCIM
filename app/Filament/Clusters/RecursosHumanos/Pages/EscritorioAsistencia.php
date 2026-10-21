<?php

namespace App\Filament\Clusters\RecursosHumanos\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Filament\Concerns\RestringeSucursal;
use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Services\EvaluarAsistenciaService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use BackedEnum;
use Filament\Pages\Page;

class EscritorioAsistencia extends Page
{
    use HasRolePageAccess;
    use RestringeSucursal;

    protected string $view = 'filament.clusters.recursos-humanos.pages.escritorio-asistencia';

    protected static ?string $cluster = RecursosHumanosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Dashboard de asistencia';

    protected static string|null|\UnitEnum $navigationGroup = 'Indicadores';

    protected static ?string $title = 'Dashboard de asistencia';

    protected static ?int $navigationSort = -100;

    public ?string $fecha = null;

    public ?int $sucursal_id = null;

    public function mount(): void
    {
        $this->fecha = now()->toDateString();
        $this->inicializarSucursalUsuario();
    }

    public function updatedFecha(): void
    {
        unset($this->resumen);
    }

    public function updatedSucursalId(): void
    {
        unset($this->resumen);
    }

    public function getSucursalesProperty(): Collection
    {
        return $this->sucursalesDisponibles();
    }

    #[Computed]
    public function resumen(): Collection
    {
        return app(EvaluarAsistenciaService::class)->evaluar(
            $this->fecha ?? now()->toDateString(),
            $this->sucursalEfectiva() ?? $this->sucursal_id,
        );
    }

    #[Computed]
    public function totales(): array
    {
        return [
            'programados' => $this->resumen->count(),
            'presentes' => $this->resumen->whereNotIn('estado', ['ausente', 'descanso', 'sin_horario'])->count(),
            'puntuales' => $this->resumen->whereIn('estado', ['puntual', 'tolerancia'])->count(),
            'retardos' => $this->resumen->where('estado', 'retardo')->count(),
            'ausentes' => $this->resumen->where('estado', 'ausente')->count(),
            'sin_salida' => $this->resumen->where('estado', 'sin_salida')->count(),
        ];
    }
}
