<?php

namespace App\Filament\Clusters\RecursosHumanos\Pages;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Concerns\HasRolePageAccess;
use App\Filament\Concerns\RestringeSucursal;
use App\Models\Empleado;
use App\Services\GenerarReporteAsistenciaService;
use BackedEnum;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Filament\Pages\Page;

class ReporteAsistencia extends Page
{
    use HasRolePageAccess;
    use RestringeSucursal;

    protected string $view = 'filament.clusters.recursos-humanos.pages.reporte-asistencia';

    protected static ?string $cluster = RecursosHumanosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reporte de asistencia';

    protected static ?string $title = 'Reporte de asistencia';

    public ?string $fecha_inicio = null;

    public ?string $fecha_fin = null;

    public ?int $sucursal_id = null;

    public ?int $empleado_id = null;

    public function mount(): void
    {
        $this->fecha_inicio = now()->startOfWeek()->toDateString();
        $this->fecha_fin = now()->toDateString();
        $this->inicializarSucursalUsuario();
    }

    public function getSucursalesProperty(): Collection
    {
        return $this->sucursalesDisponibles();
    }

    public function getEmpleadosProperty(): Collection
    {
        return Empleado::query()
            ->where('estatus', 'activo')
            ->when($this->sucursalEfectiva() ?? $this->sucursal_id, fn ($query, $sucursal) => $query->where('sucursal_id', $sucursal))
            ->orderBy('apellido_paterno')
            ->get()
            ->mapWithKeys(fn (Empleado $empleado): array => [
                $empleado->id => "{$empleado->numero_empleado} - {$empleado->nombre_completo}",
            ]);
    }

    public function updatedFechaInicio(): void
    {
        unset($this->resumen);
    }

    public function updatedFechaFin(): void
    {
        unset($this->resumen);
    }

    public function updatedSucursalId(): void
    {
        $this->empleado_id = null;
        unset($this->resumen);
    }

    public function updatedEmpleadoId(): void
    {
        unset($this->resumen);
    }

    #[Computed]
    public function resumen(): Collection
    {
        return app(GenerarReporteAsistenciaService::class)->generar(
            $this->fecha_inicio ?? now()->startOfWeek()->toDateString(),
            $this->fecha_fin ?? now()->toDateString(),
            $this->sucursalEfectiva() ?? $this->sucursal_id,
            $this->empleado_id,
        );
    }

    public function exportarCsv()
    {
        $rows = $this->resumen;
        $inicio = $this->fecha_inicio;
        $fin = $this->fecha_fin;

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Número', 'Empleado', 'Sucursal', 'Programados', 'Laborados', 'Puntuales', 'Retardos', 'Faltas', 'Sin salida', 'Min. retardo', 'Min. comida excedida', 'Min. salida anticipada', 'Min. efectivos', 'Min. extra autorizados']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['numero_empleado'],
                    $row['nombre'],
                    $row['sucursal'],
                    $row['dias_programados'],
                    $row['dias_laborados'],
                    $row['puntuales'],
                    $row['retardos'],
                    $row['faltas'],
                    $row['sin_salida'],
                    $row['minutos_retardo'],
                    $row['minutos_comida_excedidos'],
                    $row['minutos_salida_anticipada'],
                    $row['minutos_efectivos'],
                    $row['horas_extra_autorizadas'],
                ]);
            }
            fclose($handle);
        }, "reporte-asistencia-{$inicio}-{$fin}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
