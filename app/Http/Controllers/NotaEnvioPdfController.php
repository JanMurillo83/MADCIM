<?php

namespace App\Http\Controllers;

use App\Models\NotaEnvio;
use Barryvdh\DomPDF\Facade\Pdf;

class NotaEnvioPdfController extends Controller
{
    /**
     * Genera PDF de nota de envío en formato ticket (80mm)
     */
    public function ticket($id)
    {
        $notaEnvio = NotaEnvio::with(['cliente', 'direccionEntrega', 'notaVentaRenta', 'partidas.producto'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-envio.ticket', [
            'notaEnvio' => $notaEnvio,
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream("nota-envio-{$notaEnvio->serie}-{$notaEnvio->folio}-ticket.pdf");
    }
}
