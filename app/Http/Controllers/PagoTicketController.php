<?php

namespace App\Http\Controllers;

use App\Models\Pagos;
use Barryvdh\DomPDF\Facade\Pdf;

class PagoTicketController extends Controller
{
    public function __invoke(int $id)
    {
        $pago = Pagos::query()->with(['cliente', 'user', 'caja'])->findOrFail($id);

        $documento = match ($pago->documento_tipo) {
            'notas_venta_renta' => \App\Models\NotasVentaRenta::query()->with('partidas')->findOrFail($pago->documento_id),
            'notas_venta_venta' => \App\Models\NotasVentaVenta::query()->with('partidas')->findOrFail($pago->documento_id),
            default => null,
        };

        abort_unless($documento, 404);

        $pdf = Pdf::loadView('pdf.pagos.ticket', compact('pago', 'documento'))
            ->setPaper([0, 0, 226.77, 620], 'portrait');

        return $pdf->stream("pago-{$pago->id}-ticket.pdf");
    }
}
