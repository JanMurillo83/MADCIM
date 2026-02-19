<?php

namespace App\Http\Controllers;

use App\Models\Cotizaciones;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CotizacionPdfController extends Controller
{
    /**
     * Genera PDF de cotización en formato ticket (80mm)
     */
    public function ticket($id)
    {
        $cotizacion = Cotizaciones::with(['cliente', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.cotizaciones.ticket', [
            'cotizacion' => $cotizacion
        ]);

        // Configurar tamaño de ticket (80mm x auto)
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm width

        return $pdf->stream("cotizacion-{$cotizacion->serie}-{$cotizacion->folio}-ticket.pdf");
    }

    /**
     * Genera PDF de cotización en formato carta
     */
    public function carta($id)
    {
        $cotizacion = Cotizaciones::with(['cliente', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.cotizaciones.carta', [
            'cotizacion' => $cotizacion
        ]);

        // Configurar tamaño carta
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("cotizacion-{$cotizacion->serie}-{$cotizacion->folio}-carta.pdf");
    }

    /**
     * Descarga PDF de cotización en formato ticket
     */
    public function descargarTicket($id)
    {
        $cotizacion = Cotizaciones::with(['cliente', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.cotizaciones.ticket', [
            'cotizacion' => $cotizacion
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->download("cotizacion-{$cotizacion->serie}-{$cotizacion->folio}-ticket.pdf");
    }

    /**
     * Descarga PDF de cotización en formato carta
     */
    public function descargarCarta($id)
    {
        $cotizacion = Cotizaciones::with(['cliente', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.cotizaciones.carta', [
            'cotizacion' => $cotizacion
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->download("cotizacion-{$cotizacion->serie}-{$cotizacion->folio}-carta.pdf");
    }
}
