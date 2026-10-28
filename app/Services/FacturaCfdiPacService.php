<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\FacturasCfdi;
use DOMDocument;
use DOMNodeList;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FacturaCfdiPacService
{
    public function __construct(
        private readonly FacturaCfdiXmlBuilder $xmlBuilder,
        private readonly ProdigiaPacService $pac,
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function timbrar(FacturasCfdi $factura): array
    {
        if ($factura->estatus === 'Cancelada' || filled($factura->cfdi_fecha_cancelacion)) {
            return ['success' => false, 'message' => 'No se puede timbrar una factura cancelada.'];
        }

        if (filled($factura->cfdi_uuid)) {
            return ['success' => false, 'message' => 'La factura ya cuenta con un UUID y no puede volver a timbrarse.'];
        }

        if ($factura->regimen_fiscal_receptor === '616' && $factura->uso_cfdi !== 'S01') {
            $factura->forceFill(['uso_cfdi' => 'S01'])->save();
        }

        $configuracion = Configuracion::query()->first();
        if (! $configuracion) {
            return $this->falloTimbrado($factura, 'No existe la configuración general de la empresa.');
        }

        try {
            $xml = $this->xmlBuilder->construir($factura, $configuracion);
            $respuesta = $this->decodificarRespuesta($this->pac->timbrar(
                $xml,
                $this->rutaCsd($configuracion->sello_cer),
                $this->rutaCsd($configuracion->sello_key),
                (string) $configuracion->sello_pass,
            ));
        } catch (RuntimeException $exception) {
            return $this->falloTimbrado($factura, $exception->getMessage());
        }

        if (($respuesta['operacion'] ?? null) !== 'timbrar' || ! is_string($respuesta['cfdi'] ?? null)) {
            return $this->falloTimbrado(
                $factura,
                (string) ($respuesta['mensaje'] ?? 'PRODIGIA no devolvió un CFDI timbrado.'),
                [
                    'codigo_pac' => $respuesta['codigo'] ?? null,
                    ...$this->diagnosticoXml($xml),
                ],
            );
        }

        $xmlTimbrado = $respuesta['cfdi'];
        $datosTimbrado = $this->datosDelXmlTimbrado($xmlTimbrado);
        if ($datosTimbrado === null) {
            return $this->falloTimbrado($factura, 'PRODIGIA respondió sin un Timbre Fiscal Digital válido.');
        }

        $factura->forceFill([
            'cfdi_uuid' => $datosTimbrado['uuid'],
            'cfdi_version' => '4.0',
            'cfdi_xml' => $xmlTimbrado,
            'cfdi_no_certificado' => $datosTimbrado['no_certificado'],
            'cfdi_certificado' => $datosTimbrado['certificado'],
            'cfdi_sello' => $datosTimbrado['sello'],
            'cfdi_fecha_timbrado' => $datosTimbrado['fecha_timbrado'],
            'cfdi_estatus_sat' => 'Vigente',
            'cfdi_estatus_cancelacion' => null,
            'estatus' => 'Timbrada',
        ])->save();

        return [
            'success' => true,
            'message' => 'CFDI timbrado correctamente. UUID: '.$datosTimbrado['uuid'],
        ];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function cancelarSinTimbrar(FacturasCfdi $factura): array
    {
        if (filled($factura->cfdi_uuid)) {
            return ['success' => false, 'message' => 'La factura ya está timbrada; solicita su cancelación al PAC.'];
        }

        if ($factura->estatus === 'Cancelada') {
            return ['success' => false, 'message' => 'La factura ya está cancelada.'];
        }

        $factura->forceFill([
            'estatus' => 'Cancelada',
            'saldo_pendiente' => 0,
            'cfdi_fecha_cancelacion' => null,
        ])->save();

        return [
            'success' => true,
            'message' => 'La factura sin timbre se canceló localmente. Sus notas de origen quedaron disponibles nuevamente.',
        ];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function cancelar(FacturasCfdi $factura, string $motivo, string $folioSustitucion = ''): array
    {
        if (blank($factura->cfdi_uuid)) {
            return ['success' => false, 'message' => 'La factura no tiene un UUID timbrado para cancelar.'];
        }

        if (filled($factura->cfdi_fecha_cancelacion)) {
            return ['success' => false, 'message' => 'El CFDI ya está marcado como cancelado.'];
        }

        if ($factura->cfdi_estatus_cancelacion === 'Solicitud enviada') {
            return ['success' => false, 'message' => 'Ya existe una solicitud de cancelación pendiente de confirmación.'];
        }

        $motivo = trim($motivo);
        $folioSustitucion = strtoupper(trim($folioSustitucion));
        $motivoSat = DB::table('sat_motivo_cancelacion')
            ->where('clave', $motivo)
            ->first();

        if (! $motivoSat) {
            return ['success' => false, 'message' => 'Selecciona un motivo de cancelación SAT válido.'];
        }

        if ($motivoSat->requiere_folio_sustitucion && blank($folioSustitucion)) {
            return ['success' => false, 'message' => 'El motivo seleccionado requiere el UUID del CFDI sustituto.'];
        }

        $configuracion = Configuracion::query()->first();
        if (! $configuracion) {
            return ['success' => false, 'message' => 'No existe la configuración general de la empresa.'];
        }

        try {
            $respuesta = $this->decodificarRespuesta($this->pac->cancelar(
                $this->rutaCsd($configuracion->sello_key),
                $this->rutaCsd($configuracion->sello_cer),
                (string) $configuracion->sello_pass,
                (string) $factura->cfdi_uuid,
                (string) $factura->rfc_emisor,
                $motivo,
                $folioSustitucion,
            ));
        } catch (RuntimeException $exception) {
            return ['success' => false, 'message' => $exception->getMessage()];
        }

        if (($respuesta['operacion'] ?? null) !== 'cancelar'
            || ($respuesta['resultado'] ?? null) !== 'success') {
            $mensaje = (string) ($respuesta['mensaje'] ?? 'PRODIGIA rechazó la solicitud de cancelación.');
            Log::warning('La solicitud de cancelación CFDI fue rechazada por el PAC.', [
                'factura_cfdi_id' => $factura->id,
                'mensaje' => $mensaje,
            ]);

            return ['success' => false, 'message' => $mensaje];
        }

        $factura->forceFill([
            'cfdi_motivo_cancelacion' => $motivo,
            'cfdi_folio_sustitucion' => $motivo === '01' ? $folioSustitucion : null,
            'cfdi_estatus_cancelacion' => 'Solicitud enviada',
        ])->save();

        return [
            'success' => true,
            'message' => 'PRODIGIA aceptó la solicitud. El CFDI permanece pendiente de confirmación de cancelación.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodificarRespuesta(string $respuesta): array
    {
        $datos = json_decode($respuesta, true);

        return is_array($datos) ? $datos : [];
    }

    private function rutaCsd(?string $archivo): string
    {
        $archivo = trim((string) $archivo);

        if ($archivo === '' || ! Storage::disk('local')->exists($archivo)) {
            throw new RuntimeException('Falta el certificado o la llave CSD en la configuración de la empresa.');
        }

        return Storage::disk('local')->path($archivo);
    }

    /**
     * @return array{uuid: string, fecha_timbrado: string, no_certificado: ?string, certificado: ?string, sello: ?string}|null
     */
    private function datosDelXmlTimbrado(string $xml): ?array
    {
        $documento = new DOMDocument;
        $estadoErroresLibxml = libxml_use_internal_errors(true);
        $xmlValido = $documento->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($estadoErroresLibxml);

        if (! $xmlValido) {
            return null;
        }

        $xpath = new DOMXPath($documento);
        $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
        $nodoTimbre = $xpath->query('//tfd:TimbreFiscalDigital')?->item(0);
        $uuid = strtoupper(trim((string) $nodoTimbre?->attributes?->getNamedItem('UUID')?->nodeValue));

        if ($nodoTimbre === null || $uuid === '') {
            return null;
        }

        $fechaTimbrado = (string) $nodoTimbre->attributes?->getNamedItem('FechaTimbrado')?->nodeValue;
        $nodoComprobante = $xpath->query('/*[local-name()="Comprobante"]')?->item(0);

        return [
            'uuid' => $uuid,
            'fecha_timbrado' => $fechaTimbrado !== '' ? $fechaTimbrado : now()->toDateTimeString(),
            'no_certificado' => $nodoComprobante?->attributes?->getNamedItem('NoCertificado')?->nodeValue,
            'certificado' => $nodoComprobante?->attributes?->getNamedItem('Certificado')?->nodeValue,
            'sello' => $nodoComprobante?->attributes?->getNamedItem('Sello')?->nodeValue,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function diagnosticoXml(string $xml): array
    {
        $documento = new DOMDocument;
        $estadoErroresLibxml = libxml_use_internal_errors(true);
        $xmlValido = $documento->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($estadoErroresLibxml);

        if (! $xmlValido) {
            return [
                'xml_sha256' => hash('sha256', $xml),
                'xml_analizable' => false,
            ];
        }

        $xpath = new DOMXPath($documento);
        $nodoComprobante = $xpath->query('/*[local-name()="Comprobante"]')?->item(0);
        $contarDecimales = static function (?DOMNodeList $nodos, string $atributo = 'Importe'): array {
            $decimales = [];

            foreach ($nodos ?? [] as $nodo) {
                $importe = (string) $nodo->attributes?->getNamedItem($atributo)?->nodeValue;
                $puntoDecimal = strpos($importe, '.');
                $decimales[] = $puntoDecimal === false ? 0 : strlen($importe) - $puntoDecimal - 1;
            }

            return $decimales;
        };

        return [
            'xml_sha256' => hash('sha256', $xml),
            'xml_analizable' => true,
            'moneda' => $nodoComprobante?->attributes?->getNamedItem('Moneda')?->nodeValue,
            'concepto_traslado_importe_decimales' => $contarDecimales($xpath->query(
                '//*[local-name()="Concepto"]/*[local-name()="Impuestos"]/*[local-name()="Traslados"]/*[local-name()="Traslado"]',
            )),
            'resumen_traslado_importe_decimales' => $contarDecimales($xpath->query(
                '/*[local-name()="Comprobante"]/*[local-name()="Impuestos"]/*[local-name()="Traslados"]/*[local-name()="Traslado"]',
            )),
            'resumen_traslado_base_decimales' => $contarDecimales($xpath->query(
                '/*[local-name()="Comprobante"]/*[local-name()="Impuestos"]/*[local-name()="Traslados"]/*[local-name()="Traslado"]',
            ), 'Base'),
        ];
    }

    /**
     * @param  array<string, mixed>  $diagnostico
     * @return array{success: false, message: string}
     */
    private function falloTimbrado(FacturasCfdi $factura, string $mensaje, array $diagnostico = []): array
    {
        $factura->forceFill([
            'cfdi_estatus_sat' => 'Error de timbrado',
            'estatus' => 'Borrador',
        ])->save();
        Log::warning('No se pudo timbrar el CFDI.', [
            ...$diagnostico,
            'factura_cfdi_id' => $factura->id,
            'mensaje' => $mensaje,
        ]);

        return ['success' => false, 'message' => $mensaje];
    }
}
