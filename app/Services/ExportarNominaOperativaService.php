<?php

namespace App\Services;

use App\Models\ConceptoNomina;
use App\Models\NominaSemanal;
use App\Models\PeriodoNomina;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class ExportarNominaOperativaService
{
    public function registros(PeriodoNomina $periodo): Collection
    {
        return NominaSemanal::query()
            ->with('empleado')
            ->where('periodo_nomina_id', $periodo->id)
            ->orderBy('empleado_id')
            ->get();
    }

    public function csv(PeriodoNomina $periodo)
    {
        $registros = $this->registros($periodo);

        return response()->streamDownload(function () use ($registros): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Número', 'Empleado', 'Sueldo base', 'Días programados', 'Días trabajados', 'Días descanso laborados', 'Días festivos', 'Faltas', 'Minutos retardo', 'Descuento faltas', 'Descuento retardos', 'Horas extra', 'Bonos', 'Aguinaldo', 'Apoyos', 'Anticipos', 'Préstamos', 'Otros descuentos', 'Total']);
            foreach ($registros as $nomina) {
                fputcsv($handle, [
                    $nomina->empleado?->numero_empleado,
                    $nomina->empleado?->nombre_completo,
                    $nomina->sueldo_base,
                    $nomina->dias_programados,
                    $nomina->dias_trabajados,
                    $nomina->dias_descanso_laborados,
                    $nomina->dias_festivos,
                    $nomina->faltas,
                    $nomina->minutos_retardo,
                    $nomina->descuento_faltas,
                    $nomina->descuento_retardos,
                    $nomina->horas_extra,
                    $nomina->bonos,
                    $nomina->aguinaldo,
                    $nomina->apoyos,
                    $nomina->anticipos,
                    $nomina->prestamos,
                    $nomina->otros_descuentos,
                    $nomina->total,
                ]);
            }
            fclose($handle);
        }, "nomina-{$periodo->fecha_inicio->toDateString()}-{$periodo->fecha_fin->toDateString()}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(PeriodoNomina $periodo)
    {
        return $this->pdfDocument($periodo)->download("nomina-{$periodo->fecha_inicio->toDateString()}-{$periodo->fecha_fin->toDateString()}.pdf");
    }

    public function previewPdf(PeriodoNomina $periodo)
    {
        return $this->pdfDocument($periodo)->stream("nomina-{$periodo->fecha_inicio->toDateString()}-{$periodo->fecha_fin->toDateString()}.pdf");
    }

    private function pdfDocument(PeriodoNomina $periodo)
    {
        return Pdf::loadView('filament.clusters.recursos-humanos.resources.periodos-nomina.pdf.nomina-operativa', [
            'periodo' => $periodo,
            'nominas' => $this->registros($periodo),
        ])->setPaper('letter', 'landscape');
    }

    public function recibo(NominaSemanal $nomina)
    {
        return $this->reciboDocument($nomina)->download(
            "recibo-{$nomina->empleado?->numero_empleado}-{$nomina->periodo?->fecha_inicio->toDateString()}.pdf",
        );
    }

    public function previewRecibo(NominaSemanal $nomina)
    {
        return $this->reciboDocument($nomina)->stream(
            "recibo-{$nomina->empleado?->numero_empleado}-{$nomina->periodo?->fecha_inicio->toDateString()}.pdf",
        );
    }

    private function reciboDocument(NominaSemanal $nomina)
    {
        $nomina->loadMissing(['empleado.sucursal', 'periodo']);
        $conceptos = ConceptoNomina::query()
            ->where('periodo_nomina_id', $nomina->periodo_nomina_id)
            ->where('empleado_id', $nomina->empleado_id)
            ->orderBy('tipo')
            ->orderBy('created_at')
            ->get();

        return Pdf::loadView('filament.clusters.recursos-humanos.resources.periodos-nomina.pdf.recibo-nomina', [
            'nomina' => $nomina,
            'empleado' => $nomina->empleado,
            'periodo' => $nomina->periodo,
            'conceptos' => $conceptos,
        ])->setPaper('letter', 'portrait');
    }
}
