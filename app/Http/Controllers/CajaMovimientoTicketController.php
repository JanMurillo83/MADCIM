<?php

namespace App\Http\Controllers;

use App\Models\CajaMovimiento;
use Barryvdh\DomPDF\Facade\Pdf;

class CajaMovimientoTicketController extends Controller
{
    public function devolucionDeposito(int $id)
    {
        $movimiento = CajaMovimiento::query()
            ->with(['caja', 'user', 'movimentable.documentoOrigen.cliente'])
            ->where('tipo', 'Egreso')
            ->where('fuente', 'Devolución depósito renta')
            ->findOrFail($id);

        $devolucion = $movimiento->movimentable;
        abort_unless($devolucion, 404);

        $pdf = Pdf::loadView('pdf.caja-movimientos.devolucion-deposito-ticket', [
            'movimiento' => $movimiento,
            'devolucion' => $devolucion,
        ])->setPaper([0, 0, 226.77, 500], 'portrait');

        return $pdf->stream("devolucion-deposito-{$movimiento->id}-ticket.pdf");
    }
}
