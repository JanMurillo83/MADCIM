<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Barryvdh\DomPDF\Facade\Pdf;

class CajaCierreTicketController extends Controller
{
    public function __invoke(int $id)
    {
        $caja = Caja::query()
            ->with(['usuarioApertura', 'usuarioCierre', 'sucursal'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.cajas.cierre-ticket', [
            'caja' => $caja,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('cierre-caja-' . $caja->id . '.pdf');
    }
}
