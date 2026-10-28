<?php

namespace App\Services;

use App\Models\Configuracion;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ProdigiaPacService
{
    public function timbrar(string $xml, string $certificadoPath, string $llavePath, string $passwordLlave): string
    {
        $configuracion = Configuracion::query()->first();
        $validationMessage = $this->validateConfiguration($configuracion, 'pac_timbrado_endpoint');

        if ($validationMessage !== null) {
            return $this->failure('timbrar', $validationMessage);
        }

        if (blank($xml)) {
            return $this->failure('timbrar', 'No se recibió el XML CFDI para timbrar.');
        }

        if (blank($passwordLlave)) {
            return $this->failure('timbrar', 'Falta la contraseña de la llave privada CSD del emisor.');
        }

        if (! is_file($certificadoPath) || ! is_readable($certificadoPath)) {
            return $this->failure('timbrar', 'No se pudo leer el certificado CSD del emisor.');
        }

        if (! is_file($llavePath) || ! is_readable($llavePath)) {
            return $this->failure('timbrar', 'No se pudo leer la llave privada CSD del emisor.');
        }

        $certificado = file_get_contents($certificadoPath);
        $llave = file_get_contents($llavePath);

        if ($certificado === false || $llave === false) {
            return $this->failure('timbrar', 'No se pudieron leer los archivos CSD del emisor.');
        }

        $payload = [
            'contrato' => trim((string) $configuracion->pac_contract),
            'xmlBase64' => base64_encode($xml),
            'certBase64' => base64_encode($certificado),
            'keyBase64' => base64_encode($llave),
            'keyPass' => $passwordLlave,
            'modo_prueba' => (bool) $configuracion->pac_modo_prueba,
        ];

        try {
            $response = Http::withBasicAuth(
                trim((string) $configuracion->pac_user),
                (string) $configuracion->pac_password,
            )
                ->withHeaders(['X-PAC-Contract' => $payload['contrato']])
                ->timeout(45)
                ->acceptJson()
                ->asJson()
                ->post((string) $configuracion->pac_timbrado_endpoint, $payload);
        } catch (ConnectionException $exception) {
            return $this->failure('timbrar', 'No fue posible conectar con PRODIGIA: '.$exception->getMessage());
        }

        $responseBody = $response->body();
        $responseData = $response->json();
        $cfdi = $this->extractXml($responseData);
        $cfdi ??= $this->extractXml($responseBody);
        $message = $this->responseMessage($responseData, $responseBody) ?? ($response->successful()
            ? 'El PAC no devolvió el CFDI timbrado.'
            : 'PRODIGIA rechazó la solicitud de timbrado.');

        if (! $response->successful() || $cfdi === null || $this->responseIndicatesFailure($responseData, $responseBody)) {
            return $this->encode([
                'operacion' => 'timbrar',
                'codigo' => $this->responseCode($responseData, $response->status(), $responseBody),
                'mensaje' => $message,
                'cfdi' => null,
            ]);
        }

        return $this->encode([
            'operacion' => 'timbrar',
            'codigo' => $this->responseCode($responseData, $response->status(), $responseBody),
            'mensaje' => $this->responseMessage($responseData, $responseBody) ?? 'CFDI timbrado correctamente.',
            'cfdi' => $cfdi,
        ]);
    }

    public function cancelar(
        string $llavePath,
        string $certificadoPath,
        string $passwordLlave,
        string $uuid,
        string $rfcEmisor,
        string $motivo,
        string $folioSustitucion,
    ): string {
        $configuracion = Configuracion::query()->first();
        $validationMessage = $this->validateConfiguration($configuracion, 'pac_cancelacion_endpoint');

        if ($validationMessage !== null) {
            return $this->failure('cancelar', $validationMessage);
        }

        if (blank($uuid) || blank($rfcEmisor) || blank($motivo)) {
            return $this->failure('cancelar', 'Faltan datos del CFDI requeridos para solicitar la cancelación.');
        }

        if (blank($passwordLlave)) {
            return $this->failure('cancelar', 'Falta la contraseña de la llave privada CSD del emisor.');
        }

        if (! is_file($certificadoPath) || ! is_readable($certificadoPath)) {
            return $this->failure('cancelar', 'No se pudo leer el certificado CSD del emisor.');
        }

        if (! is_file($llavePath) || ! is_readable($llavePath)) {
            return $this->failure('cancelar', 'No se pudo leer la llave privada CSD del emisor.');
        }

        $certificado = file_get_contents($certificadoPath);
        $llave = file_get_contents($llavePath);

        if ($certificado === false || $llave === false) {
            return $this->failure('cancelar', 'No se pudieron leer los archivos CSD del emisor.');
        }

        $uuidEntry = strtoupper(trim($uuid)).'|'.trim($motivo).'|'.($motivo === '01' ? strtoupper(trim($folioSustitucion)) : '');
        $opciones = ['motivo' => trim($motivo)];

        if ($motivo === '01' && trim($folioSustitucion) !== '') {
            $opciones['folioFiscalSustitucion'] = strtoupper(trim($folioSustitucion));
        }

        try {
            $response = Http::withBasicAuth(
                trim((string) $configuracion->pac_user),
                (string) $configuracion->pac_password,
            )
                ->timeout(45)
                ->acceptJson()
                ->get((string) $configuracion->pac_cancelacion_endpoint, [
                    'contrato' => trim((string) $configuracion->pac_contract),
                    'rfcEmisor' => strtoupper(trim($rfcEmisor)),
                    'uuids' => $uuidEntry,
                    'certBase64' => base64_encode($certificado),
                    'keyBase64' => base64_encode($llave),
                    'keyPass' => $passwordLlave,
                    'opciones' => json_encode($opciones, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'modo_prueba' => (bool) $configuracion->pac_modo_prueba,
                ]);
        } catch (ConnectionException $exception) {
            return $this->failure('cancelar', 'No fue posible conectar con PRODIGIA: '.$exception->getMessage());
        }

        $responseData = $response->json();
        $message = $this->responseMessage($responseData) ?? ($response->successful()
            ? 'Solicitud de cancelación enviada a PRODIGIA.'
            : 'PRODIGIA rechazó la solicitud de cancelación.');

        if (! $response->successful() || $this->responseIndicatesFailure($responseData)) {
            return $this->encode([
                'operacion' => 'cancelar',
                'codigo' => $this->responseCode($responseData, $response->status()),
                'mensaje' => $message,
                'acuse' => null,
                'resultado' => 'error',
            ]);
        }

        return $this->encode([
            'operacion' => 'cancelar',
            'codigo' => $this->responseCode($responseData, $response->status()),
            'mensaje' => $message,
            'acuse' => data_get($responseData, 'acuse')
                ?? data_get($responseData, 'data.acuse')
                ?? (str_contains(strtolower((string) $response->header('Content-Type')), 'xml') ? $response->body() : null),
            'resultado' => 'success',
        ]);
    }

    private function validateConfiguration(?Configuracion $configuracion, string $endpointField): ?string
    {
        if ($configuracion === null) {
            return 'No existe la configuración general de la empresa.';
        }

        if (blank($configuracion->{$endpointField})) {
            return 'Configure el endpoint de PRODIGIA en Configuración.';
        }

        $endpoint = (string) $configuracion->{$endpointField};

        if (filter_var($endpoint, FILTER_VALIDATE_URL) === false || parse_url($endpoint, PHP_URL_SCHEME) !== 'https') {
            return 'El endpoint de PRODIGIA debe ser una URL HTTPS válida.';
        }

        if (blank($configuracion->pac_contract)) {
            return 'Configure el contrato PRODIGIA en Configuración.';
        }

        if (blank($configuracion->pac_user) || blank($configuracion->pac_password)) {
            return 'Configure el usuario y la contraseña de PRODIGIA en Configuración.';
        }

        return null;
    }

    private function extractXml(mixed $value): ?string
    {
        if (is_array($value)) {
            foreach (['xml_cfdi', 'xml', 'cfdi', 'xmlBase64', 'data'] as $key) {
                if (array_key_exists($key, $value)) {
                    $xml = $this->extractXml($value[$key]);

                    if ($xml !== null) {
                        return $xml;
                    }
                }
            }

            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (str_starts_with($value, '<')) {
            $xmlTimbrado = $this->extractXmlFromServicioTimbrado($value);

            if ($xmlTimbrado !== null) {
                return $xmlTimbrado;
            }

            return stripos($value, 'servicioTimbrado') === false ? $value : null;
        }

        $decodedJson = json_decode($value, true);

        if (is_array($decodedJson)) {
            return $this->extractXml($decodedJson);
        }

        $decodedBase64 = base64_decode($value, true);

        if (is_string($decodedBase64) && str_starts_with(ltrim($decodedBase64), '<')) {
            return $this->extractXml($decodedBase64);
        }

        return null;
    }

    private function extractXmlFromServicioTimbrado(string $xml): ?string
    {
        $document = new DOMDocument;
        $previousLibxmlState = libxml_use_internal_errors(true);
        $isValidXml = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);

        if (! $isValidXml) {
            return null;
        }

        $xpath = new DOMXPath($document);
        $servicioTimbrado = $xpath->query('//*[local-name()="servicioTimbrado"]')?->item(0);

        if ($servicioTimbrado === null) {
            return null;
        }

        $xmlBase64 = trim((string) $xpath->query('.//*[local-name()="xmlBase64"]', $servicioTimbrado)?->item(0)?->textContent);
        $xmlDecoded = base64_decode($xmlBase64, true);

        if (! is_string($xmlDecoded) || ! str_starts_with(ltrim($xmlDecoded), '<')) {
            return null;
        }

        return trim($xmlDecoded);
    }

    private function responseMessage(mixed $responseData, ?string $responseBody = null): ?string
    {
        foreach (['mensaje', 'message', 'data.message', 'data.mensaje'] as $key) {
            $message = data_get($responseData, $key);

            if (is_string($message) && trim($message) !== '') {
                return trim($message);
            }
        }

        return $responseBody !== null
            ? $this->xmlResponseValue($responseBody, 'mensaje')
            : null;
    }

    private function xmlResponseValue(string $xml, string $tag): ?string
    {
        $document = new DOMDocument;
        $previousLibxmlState = libxml_use_internal_errors(true);
        $isValidXml = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);

        if (! $isValidXml) {
            return null;
        }

        $xpath = new DOMXPath($document);
        $value = $xpath->query('//*[local-name()="'.$tag.'"]')?->item(0)?->textContent;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function responseCode(mixed $responseData, int $httpStatus, ?string $responseBody = null): string|int
    {
        foreach (['codigo', 'code', 'status'] as $key) {
            $code = data_get($responseData, $key);

            if (is_string($code) || is_int($code)) {
                return $code;
            }
        }

        if ($responseBody !== null) {
            $code = $this->xmlResponseValue($responseBody, 'codigo');

            if ($code !== null) {
                return $code;
            }
        }

        return $httpStatus;
    }

    private function responseIndicatesFailure(mixed $responseData, ?string $responseBody = null): bool
    {
        if ($responseBody !== null) {
            $timbradoOk = strtolower((string) $this->xmlResponseValue($responseBody, 'timbradoOk'));

            if (in_array($timbradoOk, ['false', '0', 'no', 'error'], true)) {
                return true;
            }
        }

        if (! is_array($responseData)) {
            return false;
        }

        $code = data_get($responseData, 'codigo')
            ?? data_get($responseData, 'code')
            ?? data_get($responseData, 'status');

        if (is_numeric($code) && (int) $code >= 400) {
            return true;
        }

        foreach (['success', 'ok', 'statusOk', 'status_ok'] as $key) {
            $value = data_get($responseData, $key);

            if ($value === false || $value === 0 || $value === 'false') {
                return true;
            }
        }

        $status = strtolower((string) (data_get($responseData, 'status') ?? data_get($responseData, 'resultado.status')));

        return in_array($status, ['error', 'failed', 'failure', 'rechazado'], true);
    }

    private function failure(string $operation, string $message): string
    {
        $payload = [
            'operacion' => $operation,
            'codigo' => 500,
            'mensaje' => $message,
        ];

        if ($operation === 'timbrar') {
            $payload['cfdi'] = null;
        } else {
            $payload['acuse'] = null;
            $payload['resultado'] = 'error';
        }

        return $this->encode($payload);
    }

    private function encode(array $payload): string
    {
        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
