<?php

namespace App\Http\Controllers;

use App\Models\NominaSemanal;
use App\Models\PeriodoNomina;
use App\Services\ExportarNominaOperativaService;

class NominaPdfController extends Controller
{
    public function nomina(PeriodoNomina $periodo, ExportarNominaOperativaService $exportador)
    {
        return $exportador->pdf($periodo);
    }

    public function previewNomina(PeriodoNomina $periodo, ExportarNominaOperativaService $exportador)
    {
        return $exportador->previewPdf($periodo);
    }

    public function recibo(NominaSemanal $nomina, ExportarNominaOperativaService $exportador)
    {
        return $exportador->recibo($nomina);
    }

    public function previewRecibo(NominaSemanal $nomina, ExportarNominaOperativaService $exportador)
    {
        return $exportador->previewRecibo($nomina);
    }
}
