<?php

namespace App\Http\Controllers;

use App\Models\RequisicionCompra;
use Barryvdh\DomPDF\Facade\Pdf;

class RequisicionCompraPdfController extends Controller
{
    public function ticket($id)
    {
        $requisicion = RequisicionCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.requisiciones-compra.ticket', [
            'requisicion' => $requisicion,
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream("requisicion-{$requisicion->serie}-{$requisicion->folio}-ticket.pdf");
    }

    public function carta($id)
    {
        $requisicion = RequisicionCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.requisiciones-compra.carta', [
            'requisicion' => $requisicion,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("requisicion-{$requisicion->serie}-{$requisicion->folio}-carta.pdf");
    }

    public function descargarTicket($id)
    {
        $requisicion = RequisicionCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.requisiciones-compra.ticket', [
            'requisicion' => $requisicion,
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->download("requisicion-{$requisicion->serie}-{$requisicion->folio}-ticket.pdf");
    }

    public function descargarCarta($id)
    {
        $requisicion = RequisicionCompra::with(['proveedor', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.requisiciones-compra.carta', [
            'requisicion' => $requisicion,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->download("requisicion-{$requisicion->serie}-{$requisicion->folio}-carta.pdf");
    }
}
