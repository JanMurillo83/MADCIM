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

        $caja->setRelation('movimientos', $caja->movimientos()
            ->visibleEnCaja()
            ->when($caja->corte_id, fn ($query) => $query->where(function ($query) use ($caja): void {
                $query->where('corte_id', $caja->corte_id)
                    ->orWhere(fn ($query) => $query->whereNull('corte_id')->where('fecha', '>=', $caja->fecha_apertura));
            }))
            ->when(! $caja->corte_id && $caja->fecha_apertura, fn ($query) => $query->where('fecha', '>=', $caja->fecha_apertura))
            ->get());

        $pdf = Pdf::loadView('pdf.cajas.cierre-ticket', [
            'caja' => $caja,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('cierre-caja-' . $caja->id . '.pdf');
    }
}
