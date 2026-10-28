<?php

namespace App\Http\Controllers;

use App\Models\FacturasCfdi;
use App\Services\FacturaCfdiRepresentacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacturaCfdiRepresentacionController extends Controller
{
    public function xml(FacturasCfdi $factura): StreamedResponse
    {
        abort_if(blank($factura->cfdi_uuid) || blank($factura->cfdi_xml), 404);

        return response()->streamDownload(
            static function () use ($factura): void {
                echo $factura->cfdi_xml;
            },
            $this->nombreArchivo($factura, 'xml'),
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }

    public function pdf(FacturasCfdi $factura, FacturaCfdiRepresentacionService $representacion): Response
    {
        abort_if(blank($factura->cfdi_uuid) || blank($factura->cfdi_xml), 404);

        try {
            $datos = $representacion->datos($factura);
        } catch (RuntimeException) {
            abort(422, 'No fue posible generar la representación impresa de este CFDI.');
        }

        return Pdf::loadView('pdf.facturas-cfdi.representacion-impresa', $datos)
            ->setPaper('letter', 'portrait')
            ->stream($this->nombreArchivo($factura, 'pdf'));
    }

    private function nombreArchivo(FacturasCfdi $factura, string $extension): string
    {
        $folio = trim(implode('-', array_filter([
            $factura->serie,
            $factura->folio,
            $factura->cfdi_uuid,
        ])));
        $folio = preg_replace('/[^A-Za-z0-9._-]+/', '-', $folio) ?: (string) $factura->getKey();

        return 'CFDI-'.$folio.'.'.$extension;
    }
}
