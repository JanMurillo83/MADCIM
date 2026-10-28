<?php

namespace App\Services;

use App\Models\FacturasCfdi;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;

class FacturaCfdiRepresentacionService
{
    /**
     * @return array<string, mixed>
     */
    public function datos(FacturasCfdi $factura): array
    {
        if (blank($factura->cfdi_xml) || blank($factura->cfdi_uuid)) {
            throw new RuntimeException('La factura no tiene un CFDI timbrado disponible.');
        }

        $documento = new DOMDocument;
        $erroresLibxmlPrevios = libxml_use_internal_errors(true);
        $xmlValido = $documento->loadXML((string) $factura->cfdi_xml, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($erroresLibxmlPrevios);

        if (! $xmlValido || ! $documento->documentElement) {
            throw new RuntimeException('El XML timbrado de la factura no es válido.');
        }

        $xpath = new DOMXPath($documento);
        $comprobante = $documento->documentElement;
        $emisor = $this->hijo($comprobante, 'Emisor');
        $receptor = $this->hijo($comprobante, 'Receptor');
        $conceptosNodo = $this->hijo($comprobante, 'Conceptos');
        $impuestosNodo = $this->hijo($comprobante, 'Impuestos');
        $timbreNodo = $xpath->query('//*[local-name()="TimbreFiscalDigital"]')?->item(0);

        if (! $emisor || ! $receptor || ! $conceptosNodo || ! $timbreNodo instanceof DOMElement) {
            throw new RuntimeException('El XML no contiene los datos fiscales requeridos para imprimir el CFDI.');
        }

        $uuid = strtoupper($this->atributo($timbreNodo, 'UUID'));
        if ($uuid === '' || $uuid !== strtoupper((string) $factura->cfdi_uuid)) {
            throw new RuntimeException('El UUID del XML no coincide con el UUID guardado para la factura.');
        }

        foreach ([
            'sello del emisor' => $this->atributo($comprobante, 'Sello'),
            'número de certificado del emisor' => $this->atributo($comprobante, 'NoCertificado'),
            'fecha de certificación' => $this->atributo($timbreNodo, 'FechaTimbrado'),
            'número de certificado del SAT' => $this->atributo($timbreNodo, 'NoCertificadoSAT'),
            'sello del SAT' => $this->atributo($timbreNodo, 'SelloSAT'),
        ] as $campo => $valor) {
            if ($valor === '') {
                throw new RuntimeException("El XML timbrado no contiene el {$campo} requerido para su representación impresa.");
            }
        }

        $conceptos = [];
        foreach ($this->hijos($conceptosNodo, 'Concepto') as $concepto) {
            $impuestosConcepto = $this->hijo($concepto, 'Impuestos');
            $conceptos[] = [
                'clave_prod_serv' => $this->atributo($concepto, 'ClaveProdServ'),
                'no_identificacion' => $this->atributo($concepto, 'NoIdentificacion'),
                'cantidad' => $this->atributo($concepto, 'Cantidad'),
                'clave_unidad' => $this->atributo($concepto, 'ClaveUnidad'),
                'unidad' => $this->atributo($concepto, 'Unidad'),
                'descripcion' => $this->atributo($concepto, 'Descripcion'),
                'valor_unitario' => $this->atributo($concepto, 'ValorUnitario'),
                'importe' => $this->atributo($concepto, 'Importe'),
                'descuento' => $this->atributo($concepto, 'Descuento'),
                'impuestos' => $this->impuestos($impuestosConcepto),
            ];
        }

        $relacionados = [];
        foreach ($this->hijos($comprobante, 'CfdiRelacionados') as $grupoRelacionado) {
            foreach ($this->hijos($grupoRelacionado, 'CfdiRelacionado') as $relacionado) {
                $relacionados[] = [
                    'tipo' => $this->atributo($grupoRelacionado, 'TipoRelacion'),
                    'uuid' => $this->atributo($relacionado, 'UUID'),
                ];
            }
        }

        $selloEmisor = $this->atributo($comprobante, 'Sello');
        $cadenaOriginalTimbre = $this->cadenaOriginalTimbre($timbreNodo);
        $total = $this->atributo($comprobante, 'Total');
        $rfcEmisor = $this->atributo($emisor, 'Rfc');
        $rfcReceptor = $this->atributo($receptor, 'Rfc');
        $totalQr = rtrim(rtrim(number_format((float) $total, 6, '.', ''), '0'), '.');
        if ($totalQr === '') {
            $totalQr = '0';
        }

        $datosQr = 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx'
            .'?id='.$uuid
            .'&re='.$rfcEmisor
            .'&rr='.$rfcReceptor
            .'&tt='.$totalQr
            .'&fe='.substr($selloEmisor, -8);
        $qr = (new QRCode(new QROptions([
            'eccLevel' => EccLevel::M,
            'outputBase64' => true,
            'outputType' => QROutputInterface::GDIMAGE_PNG,
        ])))->render($datosQr);

        return [
            'comprobante' => $this->atributos($comprobante, [
                'Version', 'Serie', 'Folio', 'Fecha', 'FormaPago', 'NoCertificado', 'SubTotal',
                'Descuento', 'Moneda', 'TipoCambio', 'Total', 'TipoDeComprobante', 'Exportacion',
                'MetodoPago', 'LugarExpedicion', 'CondicionesDePago',
            ]),
            'emisor' => $this->atributos($emisor, ['Rfc', 'Nombre', 'RegimenFiscal', 'FacAtrAdquirente']),
            'receptor' => $this->atributos($receptor, [
                'Rfc', 'Nombre', 'DomicilioFiscalReceptor', 'ResidenciaFiscal', 'NumRegIdTrib',
                'RegimenFiscalReceptor', 'UsoCFDI',
            ]),
            'conceptos' => $conceptos,
            'impuestos' => $this->impuestos($impuestosNodo),
            'relacionados' => $relacionados,
            'timbre' => $this->atributos($timbreNodo, [
                'Version', 'UUID', 'FechaTimbrado', 'RfcProvCertif', 'Leyenda', 'NoCertificadoSAT',
            ]),
            'no_certificado_emisor' => $this->atributo($comprobante, 'NoCertificado'),
            'sello_emisor' => $selloEmisor,
            'sello_sat' => $this->atributo($timbreNodo, 'SelloSAT'),
            'cadena_original_timbre' => $cadenaOriginalTimbre,
            'qr' => (string) $qr,
        ];
    }

    /**
     * @return array{traslados: array<int, array<string, string>>, retenciones: array<int, array<string, string>>, total_trasladados: string, total_retenidos: string}
     */
    private function impuestos(?DOMElement $impuestos): array
    {
        $traslados = [];
        $retenciones = [];
        $trasladosNodo = $this->hijo($impuestos, 'Traslados');
        $retencionesNodo = $this->hijo($impuestos, 'Retenciones');

        foreach ($this->hijos($trasladosNodo, 'Traslado') as $traslado) {
            $traslados[] = $this->atributos($traslado, ['Base', 'Impuesto', 'TipoFactor', 'TasaOCuota', 'Importe']);
        }

        foreach ($this->hijos($retencionesNodo, 'Retencion') as $retencion) {
            $retenciones[] = $this->atributos($retencion, ['Base', 'Impuesto', 'TipoFactor', 'TasaOCuota', 'Importe']);
        }

        return [
            'traslados' => $traslados,
            'retenciones' => $retenciones,
            'total_trasladados' => $this->atributo($impuestos, 'TotalImpuestosTrasladados'),
            'total_retenidos' => $this->atributo($impuestos, 'TotalImpuestosRetenidos'),
        ];
    }

    /** @return array<string, string> */
    private function atributos(DOMElement $elemento, array $nombres): array
    {
        $atributos = [];
        foreach ($nombres as $nombre) {
            $valor = $this->atributo($elemento, $nombre);
            if ($valor !== '') {
                $atributos[$nombre] = $valor;
            }
        }

        return $atributos;
    }

    private function atributo(?DOMElement $elemento, string $nombre): string
    {
        return $elemento?->hasAttribute($nombre) ? trim($elemento->getAttribute($nombre)) : '';
    }

    private function hijo(?DOMElement $elemento, string $nombre): ?DOMElement
    {
        foreach ($elemento?->childNodes ?? [] as $nodo) {
            if ($nodo instanceof DOMElement && $nodo->localName === $nombre) {
                return $nodo;
            }
        }

        return null;
    }

    /** @return array<int, DOMElement> */
    private function hijos(?DOMElement $elemento, string $nombre): array
    {
        $resultado = [];
        foreach ($elemento?->childNodes ?? [] as $nodo) {
            if ($nodo instanceof DOMElement && $nodo->localName === $nombre) {
                $resultado[] = $nodo;
            }
        }

        return $resultado;
    }

    private function cadenaOriginalTimbre(DOMElement $timbre): string
    {
        $valores = [];
        foreach (['Version', 'UUID', 'FechaTimbrado', 'RfcProvCertif', 'Leyenda', 'SelloCFD', 'NoCertificadoSAT'] as $atributo) {
            $valor = $this->atributo($timbre, $atributo);
            if ($valor !== '') {
                $valores[] = preg_replace('/[\t\r\n ]+/', ' ', trim($valor)) ?? $valor;
            }
        }

        return '||'.implode('|', $valores).'||';
    }
}
