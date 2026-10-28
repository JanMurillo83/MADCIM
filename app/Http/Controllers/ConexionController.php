<?php

namespace App\Http\Controllers;

use App\Services\ProdigiaPacService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use SoapClient;

class ConexionController extends Controller
{
    private $wsdl;

    private ?SoapClient $client = null;

    private $response;

    public function __construct($url)
    {
        $this->wsdl = $url;
        $this->response = null;
    }

    public function operacion_timbrar($cfdi)
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return json_encode([
                'operacion' => 'timbrar',
                'codigo' => 500,
                'mensaje' => 'No existe un equipo activo para consultar la configuración fiscal del emisor.',
                'cfdi' => null,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $emisor = DB::table('datos_fiscales')->where('team_id', $tenant->id)->first();

        if (! $emisor) {
            return json_encode([
                'operacion' => 'timbrar',
                'codigo' => 500,
                'mensaje' => 'No existe la configuración fiscal del emisor para el equipo actual.',
                'cfdi' => null,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return app(ProdigiaPacService::class)->timbrar(
            $cfdi,
            public_path('storage/'.$emisor->cer),
            public_path('storage/'.$emisor->key),
            (string) $emisor->csdpass,
        );
    }

    public function operacion_timbrarRet($apikey, $cfdi)
    {
        // $res = $this->client->timbrar($apikey, $cfdi);
        $res = $this->getClient()->timbrarRetencion($apikey, $cfdi);
        $this->response = [
            'operacion' => 'timbrarRetencion',
            'codigo' => $res->code,
            'mensaje' => $res->message,
            'cfdi' => $res->data,
        ];

        return json_encode($this->response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function operacion_timbrarTFD($apikey, $cfdi)
    {
        $res = $this->getClient()->timbrarTFD($apikey, $cfdi);
        $this->response = [
            'operacion' => 'timbrarTFD',
            'codigo' => $res->code,
            'mensaje' => $res->message,
            'timbre' => $res->data,
        ];

        return json_encode($this->response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function operacion_consultar_creditos($apikey)
    {
        $res = $this->getClient()->consultarCreditosDisponibles($apikey);
        $this->response = [
            'operacion' => 'consultar_creditos',
            'codigo' => $res->code,
            'mensaje' => $res->message,
            'creditos' => $res->data,
        ];

        return json_encode($this->response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function operacion_consultarEstadoSAT($apikey, $uuid, $rfcEmisor, $rfcReceptor, $total)
    {
        $res = $this->getClient()->consultarEstadoSAT($apikey, $uuid, $rfcEmisor, $rfcReceptor, $total);
        $this->response = [
            'operacion' => 'consultarEstadoSAT',
            'Codigo del SAT' => $res->CodigoEstatus,
            'Tipo de Cancelación' => $res->EsCancelable,
            'Estado' => $res->Estado,
            'Solicitud de cancelacion' => $res->EstatusCancelacion,
        ];

        return json_encode($this->response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function operacion_cancelar($keyCSD, $cerCSD, $passCSD, $uuid, $rfcEmisor, $motivo, $folio)
    {
        return app(ProdigiaPacService::class)->cancelar(
            $keyCSD,
            $cerCSD,
            (string) $passCSD,
            $uuid,
            $rfcEmisor,
            $motivo,
            $folio,
        );
    }

    public function operacion_cancelarPFX($apikey, $pfxB64, $passPFX, $uuid, $rfcEmisor, $rfcReceptor, $total)
    {
        $res = $this->getClient()->cancelarPFX($apikey, $pfxB64, $passPFX, $uuid, $rfcEmisor, $rfcReceptor, $total);
        // # RESPUESTA ORIGINAL DEL SERVICIO ##
        // var_dump($res);
        $acuse = null;
        if ($res->status == 'success') {
            $resData = json_decode($res->data);
            $acuse = $resData->acuse;
        }
        $this->response = [
            'operacion' => 'cancelar',
            'codigo' => $res->code,
            'mensaje' => $res->message,
            'acuse' => $acuse,
            'resultado' => $res->status,
        ];

        // # GUARDAR ACUSE EN DIRECTORIO ACTUAL ##
        // file_put_contents('rsc/acuse_cancelacion.xml', $acuse);
        return json_encode($this->response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function operacion_timbrarTXT($apikey, $txtB64, $keyPEM, $cerPEM)
    {
        $res = $this->getClient()->timbrarTXT($apikey, $txtB64, $keyPEM, $cerPEM);
        $this->response = [
            'operacion' => 'timbrarTXT',
            'codigo' => $res->code,
            'mensaje' => $res->message,
            'datos' => $res->data,
        ];

        return json_encode($this->response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function operacion_timbrarTXT2($apikey, $txtB64, $keyPEM, $cerPEM, $plantilla, $logoB64)
    {
        $res = $this->getClient()->timbrarTXT2($apikey, $txtB64, $keyPEM, $cerPEM, $plantilla, $logoB64);
        $this->response = [
            'operacion' => 'timbrarTXT2',
            'codigo' => $res->code,
            'mensaje' => $res->message,
            'datos' => $res->data,
        ];

        return json_encode($this->response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function operacion_timbrarJSON($apikey, $jsonB64, $keyPEM, $cerPEM)
    {
        $res = $this->getClient()->timbrarJSON($apikey, $jsonB64, $keyPEM, $cerPEM);
        $this->response = [
            'operacion' => 'timbrarJSON',
            'codigo' => $res->code,
            'mensaje' => $res->message,
            'datos' => $res->data,
        ];

        return json_encode($this->response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function operacion_timbrarJSON2($apikey, $jsonB64, $keyPEM, $cerPEM, $plantilla, $logoB64)
    {
        $res = $this->getClient()->timbrarJSON2($apikey, $jsonB64, $keyPEM, $cerPEM, $plantilla, $logoB64);
        $this->response = [
            'operacion' => 'timbrarJSON2',
            'codigo' => $res->code,
            'mensaje' => $res->message,
            'datos' => $res->data,
        ];

        return json_encode($this->response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function getClient(): SoapClient
    {
        return $this->client ??= new SoapClient($this->wsdl);
    }
}
