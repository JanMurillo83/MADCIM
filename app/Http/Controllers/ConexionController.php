<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SoapClient;

class ConexionController extends Controller
{
    private $wsdl;
    private $client;
    private $response;

	public function __construct($url)
	{
		$this->wsdl = $url;
		$this->client = new SoapClient($this->wsdl);
		$this->response = NULL;
	}

	public function operacion_timbrar($apikey, $cfdi)
	{
		$res = $this->client->timbrar($apikey, $cfdi);
		$this->response = array(
    		'operacion' => 'timbrar',
			'codigo' => $res->code,
			'mensaje' => $res->message,
			'cfdi' => $res->data
		);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}

    public function operacion_timbrarRet($apikey, $cfdi)
	{
		//$res = $this->client->timbrar($apikey, $cfdi);
        $res = $this->client->timbrarRetencion($apikey, $cfdi);
		$this->response = array(
    		'operacion' => 'timbrarRetencion',
			'codigo' => $res->code,
			'mensaje' => $res->message,
			'cfdi' => $res->data
		);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}

	public function operacion_timbrarTFD($apikey, $cfdi)
	{
		$res = $this->client->timbrarTFD($apikey, $cfdi);
		$this->response = array(
			'operacion' => 'timbrarTFD',
			'codigo' => $res->code,
			'mensaje' => $res->message,
			'timbre' => $res->data
		);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}

    public function operacion_consultar_creditos($apikey)
	{
		$res = $this->client->consultarCreditosDisponibles($apikey);
		$this->response = array(
    		'operacion' => 'consultar_creditos',
			'codigo' => $res->code,
			'mensaje' => $res->message,
			'creditos' => $res->data
		);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}

	public function operacion_consultarEstadoSAT($apikey, $uuid, $rfcEmisor, $rfcReceptor, $total)
	{
		$res = $this->client->consultarEstadoSAT($apikey, $uuid, $rfcEmisor, $rfcReceptor, $total);
		$this->response = array(
			'operacion' => 'consultarEstadoSAT',
			'Codigo del SAT' => $res->CodigoEstatus,
			'Tipo de Cancelación' => $res->EsCancelable,
			'Estado' => $res->Estado,
			'Solicitud de cancelacion' => $res->EstatusCancelacion
		);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}

	public function operacion_cancelar($apikey, $keyCSD, $cerCSD, $passCSD, $uuid, $rfcEmisor, $rfcReceptor, $total,$motivo,$folio)
	{
        $keyCSD = base64_encode(file_get_contents($keyCSD));
        $cerCSD = base64_encode(file_get_contents($cerCSD));
		$res = $this->client->cancelar($apikey, $keyCSD, $cerCSD, $passCSD, $uuid, $rfcEmisor, $rfcReceptor, $total,$motivo,$folio);
		## RESPUESTA ORIGINAL DEL SERVICIO ##
		//var_dump($res);
		$acuse = NULL;
		if ( $res->status == "success" )
		{
			$resData = json_decode($res->data);
			$acuse = $resData->acuse;
		}
		$this->response = array(
			'operacion' => 'cancelar',
			'codigo' => $res->code,
			'mensaje' => $res->message,
			'acuse' => $acuse,
			'resultado' => $res->status
		);
		## GUARDAR ACUSE EN DIRECTORIO ACTUAL ##
		//file_put_contents('rsc/acuse_cancelacion.xml', $acuse);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}

	public function operacion_cancelarPFX($apikey, $pfxB64, $passPFX, $uuid, $rfcEmisor, $rfcReceptor, $total)
	{
		$res = $this->client->cancelarPFX($apikey, $pfxB64, $passPFX, $uuid, $rfcEmisor, $rfcReceptor, $total);
		## RESPUESTA ORIGINAL DEL SERVICIO ##
		//var_dump($res);
		$acuse = NULL;
		if ( $res->status == "success" )
		{
			$resData = json_decode($res->data);
			$acuse = $resData->acuse;
		}
		$this->response = array(
			'operacion' => 'cancelar',
			'codigo' => $res->code,
			'mensaje' => $res->message,
			'acuse' => $acuse,
			'resultado' => $res->status
		);
		## GUARDAR ACUSE EN DIRECTORIO ACTUAL ##
		//file_put_contents('rsc/acuse_cancelacion.xml', $acuse);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}

	public function operacion_timbrarTXT($apikey, $txtB64, $keyPEM, $cerPEM)
	{
		$res = $this->client->timbrarTXT($apikey, $txtB64, $keyPEM, $cerPEM);
		$this->response = array(
    		'operacion' => 'timbrarTXT',
	    	'codigo' => $res->code,
			'mensaje' => $res->message,
			'datos' => $res->data
		);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}

	public function operacion_timbrarTXT2($apikey, $txtB64, $keyPEM, $cerPEM, $plantilla, $logoB64)
	{
		$res = $this->client->timbrarTXT2($apikey, $txtB64, $keyPEM, $cerPEM, $plantilla, $logoB64);
		$this->response = array(
			'operacion' => 'timbrarTXT2',
			'codigo' => $res->code,
			'mensaje' => $res->message,
			'datos' => $res->data
		);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}

	public function operacion_timbrarJSON($apikey, $jsonB64, $keyPEM, $cerPEM)
	{
		$res = $this->client->timbrarJSON($apikey, $jsonB64, $keyPEM, $cerPEM);
		$this->response = array(
			'operacion' => 'timbrarJSON',
			'codigo' => $res->code,
			'mensaje' => $res->message,
			'datos' => $res->data
		);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}

	public function operacion_timbrarJSON2($apikey, $jsonB64, $keyPEM, $cerPEM, $plantilla,$logoB64)
	{
		$res = $this->client->timbrarJSON2($apikey, $jsonB64, $keyPEM, $cerPEM, $plantilla,$logoB64);
		$this->response = array(
			'operacion' => 'timbrarJSON2',
			'codigo' => $res->code,
			'mensaje' => $res->message,
			'datos' => $res->data
		);
		return json_encode($this->response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}
}
