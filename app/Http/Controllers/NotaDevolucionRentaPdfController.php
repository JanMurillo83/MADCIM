<?php

namespace App\Http\Controllers;

use App\Models\NotaDevolucionRenta;
use Barryvdh\DomPDF\Facade\Pdf;

class NotaDevolucionRentaPdfController extends Controller
{
    public function ticket($id)
    {
        $nota = NotaDevolucionRenta::with([
            'cliente',
            'notaOrigen',
            'notaEnvio.direccionEntrega',
            'partidas.producto',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-devolucion-renta.ticket', [
            'nota' => $nota,
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream('nota-devolucion-renta-' . ($nota->serie ?? '') . '-' . ($nota->folio ?? $nota->id) . '-ticket.pdf');
    }
}
