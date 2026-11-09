<?php

namespace App\Http\Controllers;

use App\Models\CajaMovimiento;
use App\Models\CierreDevolucionRenta;
use App\Models\DevolucionesRenta;
use App\Models\NotasVentaRenta;
use Barryvdh\DomPDF\Facade\Pdf;

class CajaMovimientoTicketController extends Controller
{
    public function devolucionDeposito(int $id)
    {
        $movimiento = CajaMovimiento::query()
            ->with(['caja', 'user', 'movimentable'])
            ->where('tipo', 'Egreso')
            ->where('fuente', 'Devolución depósito renta')
            ->findOrFail($id);

        $devolucion = $movimiento->movimentable;
        abort_unless($devolucion, 404);

        if ($devolucion instanceof CierreDevolucionRenta) {
            $cliente = $devolucion->cliente;
            $nota = NotasVentaRenta::query()->whereIn('id', $devolucion->nota_ids ?? [])->orderBy('id')->first();
        } elseif ($devolucion instanceof DevolucionesRenta) {
            $nota = $devolucion->documentoOrigen;
            $cliente = $nota?->cliente;
        } else {
            $nota = null;
            $cliente = null;
        }

        $pdf = Pdf::loadView('pdf.caja-movimientos.devolucion-deposito-ticket', [
            'movimiento' => $movimiento,
            'cliente' => $cliente,
            'nota' => $nota,
        ])->setPaper([0, 0, 226.77, 500], 'portrait');

        return $pdf->stream("devolucion-deposito-{$movimiento->id}-ticket.pdf");
    }
}
