<?php

namespace App\Http\Controllers;

use App\Models\RecepcionCompra;
use Barryvdh\DomPDF\Facade\Pdf;

class RecepcionCompraPdfController extends Controller
{
    public function ticket($id)
    {
        $recepcion = RecepcionCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.recepciones-compra.ticket', [
            'recepcion' => $recepcion,
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream("recepcion-compra-{$recepcion->serie}-{$recepcion->folio}-ticket.pdf");
    }

    public function carta($id)
    {
        $recepcion = RecepcionCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.recepciones-compra.carta', [
            'recepcion' => $recepcion,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("recepcion-compra-{$recepcion->serie}-{$recepcion->folio}-carta.pdf");
    }

    public function descargarTicket($id)
    {
        $recepcion = RecepcionCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.recepciones-compra.ticket', [
            'recepcion' => $recepcion,
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->download("recepcion-compra-{$recepcion->serie}-{$recepcion->folio}-ticket.pdf");
    }

    public function descargarCarta($id)
    {
        $recepcion = RecepcionCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.recepciones-compra.carta', [
            'recepcion' => $recepcion,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->download("recepcion-compra-{$recepcion->serie}-{$recepcion->folio}-carta.pdf");
    }
}
