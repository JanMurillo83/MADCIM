<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use Barryvdh\DomPDF\Facade\Pdf;

class OrdenCompraPdfController extends Controller
{
    public function ticket($id)
    {
        $orden = OrdenCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.ordenes-compra.ticket', [
            'orden' => $orden,
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream("orden-compra-{$orden->serie}-{$orden->folio}-ticket.pdf");
    }

    public function carta($id)
    {
        $orden = OrdenCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.ordenes-compra.carta', [
            'orden' => $orden,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("orden-compra-{$orden->serie}-{$orden->folio}-carta.pdf");
    }

    public function descargarTicket($id)
    {
        $orden = OrdenCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.ordenes-compra.ticket', [
            'orden' => $orden,
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->download("orden-compra-{$orden->serie}-{$orden->folio}-ticket.pdf");
    }

    public function descargarCarta($id)
    {
        $orden = OrdenCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.ordenes-compra.carta', [
            'orden' => $orden,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->download("orden-compra-{$orden->serie}-{$orden->folio}-carta.pdf");
    }
}
