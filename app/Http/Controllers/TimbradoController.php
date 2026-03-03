<?php

namespace App\Http\Controllers;

use App\Models\DoctosRelacionados;
use App\Models\Facturas;
use Carbon\Carbon;
use CfdiUtils\SumasPagos20\PagosWriter;
use DateTime;
use DateTimeZone;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimbradoController extends Controller
{

    public string $url = 'https://app.facturaloplus.com/ws/servicio.do?wsdl';
    public string $api_key = '18b88997a6d3461b82b7786e8a6c05ac';
    //public string $url = 'https://dev.facturaloplus.com/ws/servicio.do?wsdl';
    //public string $api_key = 'd653c0eee6664e099ead4a76d0f0e15d';
    public function CancelarFactura($factura,$receptor,$motivo,$folio):string
    {
        $objConexion = new ConexionController($this->url);
        $emidata = DB::table('datos_fiscales')->where('team_id',Filament::getTenant()->id)->first();
        $recdata = DB::table('clientes')->where('id',$receptor)->first();
        $facdata = DB::table('facturas')->where('id',$factura)->first();
        $apikey = $this->api_key;
        $cerCSD = public_path('storage/'.$emidata->cer);
        $keyCSD = public_path('storage/'.$emidata->key);
        $passCSD = $emidata->csdpass;
        $uuid = $facdata->uuid;
        $rfcEmisor =$emidata->rfc;
        $rfcReceptor = $recdata->rfc;
        $total = $facdata->total;
        $resultado = $objConexion->operacion_cancelar($apikey, $keyCSD, $cerCSD, $passCSD, $uuid, $rfcEmisor, $rfcReceptor, $total,$motivo,$folio);
        return $resultado;
    }

    public function CancelarPago($factura,$receptor,$motivo,$folio):string
    {
        $objConexion = new ConexionController($this->url);
        $emidata = DB::table('datos_fiscales')->where('team_id',Filament::getTenant()->id)->first();
        $recdata = DB::table('clientes')->where('id',$receptor)->first();
        $facdata = DB::table('pagos')->where('id',$factura)->first();
        $apikey = $this->api_key;
        $cerCSD = public_path('storage/'.$emidata->cer);
        $keyCSD = public_path('storage/'.$emidata->key);
        $passCSD = $emidata->csdpass;
        $uuid = $facdata->uuid;
        $rfcEmisor =$emidata->rfc;
        $rfcReceptor = $recdata->rfc;
        $total = $facdata->total;
        $resultado = $objConexion->operacion_cancelar($apikey, $keyCSD, $cerCSD, $passCSD, $uuid, $rfcEmisor, $rfcReceptor, $total,$motivo,$folio);
        return $resultado;
    }

    public function ConsultarFacturaSAT($factura,$receptor):string
    {
        $objConexion = new ConexionController($this->url);
        $emidata = DB::table('datos_fiscales')->where('team_id',Filament::getTenant()->id)->first();
        $recdata = DB::table('clientes')->where('id',$receptor)->first();
        $facdata = DB::table('facturas')->where('id',$factura)->first();
        $apikey = $this->api_key;
        $uuid = $facdata->uuid;
        $rfcEmisor =$emidata->rfc;
        $rfcReceptor = $recdata->rfc;
        $total = $facdata->total;
        $resultado = $objConexion->operacion_consultarEstadoSAT($apikey, $uuid, $rfcEmisor, $rfcReceptor, $total);
        return $resultado;
    }
    public function TimbrarFactura($factura,$receptor):string
    {
	    $objConexion = new ConexionController($this->url);
        $tipodoc = 'F';
        //-------------------------------------------
        $datetime = new DateTime();
        $mex_zone = new DateTimeZone('America/Mexico_City');
        $datetime->setTimezone($mex_zone);
        //$fecha = $datetime->format('Y-m-d');


        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $emidata = DB::table('datos_fiscales')->where('team_id',Filament::getTenant()->id)->first();
        $recdata = DB::table('clientes')->where('id',$receptor)->get();
        $facdata = DB::table('facturas')->where('id',$factura)->get();
        $esquema = DB::table('esquemasimps')->where('id',$facdata[0]->esquema)->first();
        $pardata = DB::table('facturas_partidas')->where('facturas_id',$factura)->get();
        $fecha = Carbon::create($facdata[0]->fecha)->format('Y-m-d');
        $hora = $datetime->format('H:i:s');
        $fechahora = $fecha.'T'.$hora;
        $nopardata = count($pardata);
        $tido = "I";
        $csdpass = $emidata->csdpass;
        $apikey = $this->api_key;
        $cerFile = public_path('storage/'.$emidata->cer);
        $keyFile = public_path('storage/'.$emidata->key);
        $keyPEM = public_path('storage/'.$emidata->rfc.'.key.pem');
        $tmpxml =public_path('storage/TMPXMLFiles/'.$recdata[0]->rfc.'.xml');
        if (file_exists($keyPEM)) {
            unlink($keyPEM);
        }
        if (file_exists($tmpxml)) {
            unlink($tmpxml);
        }
        //-------------------------------------------------------        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        //dd($keyFile, $csdpass, $keyPEM, $csdpass);
        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        $keyForFinkOk = $openssl->pemKeyProtectOut($keyPEM, $csdpass, $csdpass);
        //-------------------------------------------
        $certificado = new \CfdiUtils\Certificado\Certificado($cerFile);
        $tipo_cambio = 1;
        if($facdata[0]->moneda != 'MXN')$tipo_cambio = number_format($facdata[0]->tcambio,4);
        $comprobanteAtributos = [
            'Serie' => $facdata[0]->serie ?? 'A',
            'Folio' => $facdata[0]->folio,
            'CondicionesDePago'=>$facdata[0]->condiciones ?? "CONTADO",
            'SubTotal'=>$facdata[0]->subtotal,
            'Moneda'=>$facdata[0]->moneda,
            'TipoCambio'=>$tipo_cambio,
            'Total'=>$facdata[0]->total,
            'TipoDeComprobante'=>$tido,
            'Exportacion'=>"01",
            'MetodoPago'=>$facdata[0]->forma,
            'LugarExpedicion'=>$emidata->codigo,
            'Fecha'=>$fechahora,
            'FormaPago'=>$facdata[0]->metodo
        ];

        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();
        if($tipodoc == "N"){
            $atrib = ['TipoRelacion'=>"01"];
            $rel01 = $comprobante->addCfdiRelacionados($atrib);
            $rel01->addCfdiRelacionado([
                'UUID'=> $facdata[0]->relacionado
            ]);
        }
        $comprobante->addEmisor([
            'Rfc'=>$emidata->rfc,
            'Nombre'=>$emidata->nombre,
            'RegimenFiscal'=>$emidata->regimen
        ]);

        $comprobante->addReceptor([
            'Rfc'=>$recdata[0]->rfc,
            'Nombre'=>$recdata[0]->nombre,
            'RegimenFiscalReceptor'=>$recdata[0]->regimen,
            'DomicilioFiscalReceptor'=>$recdata[0]->codigo,
            'UsoCFDI'=>$facdata[0]->uso
        ]);
        if(count(DoctosRelacionados::where('docto_type','F')->where('docto_id',$facdata[0]->id)->get())>0){
            $doctorel = DoctosRelacionados::where('docto_type','F')->where('docto_id',$facdata[0]->id)->first();
            //dd($doctorel);
            $facrel = Facturas::where('id',$doctorel->rel_id)->first();
            $rel01 = $comprobante->addCfdiRelacionados(['TipoRelacion'=>$doctorel->rel_cause]);
            $rel01->addCfdiRelacionado([
                'UUID'=>$facrel->uuid
            ]);
        }


        for($i=0;$i<$nopardata;$i++)
        {
            $concepto1 = $comprobante->addConcepto([
                'ClaveProdServ'=>$pardata[$i]->cvesat,
                'Cantidad'=>number_format($pardata[$i]->cant, 2, '.', ''),
                'ClaveUnidad'=>$pardata[$i]->unidad,
                'ObjetoImp'=>"02",
                'Descripcion'=>$pardata[$i]->descripcion.' '.$pardata[$i]->observa ?? '',
                'ValorUnitario'=>number_format($pardata[$i]->precio, 6, '.', ''),
                'Importe'=>number_format($pardata[$i]->subtotal, 6, '.', '')
            ]);
            if($esquema->exento == 'NO') {
                $concepto1->addTraslado([
                    'Base' => number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto' => "002",
                    'TipoFactor' => "Tasa",
                    'TasaOCuota' => number_format(floatval($pardata[$i]->por_imp1) * 0.01, 6, '.', ''),
                    'Importe' => number_format($pardata[$i]->iva, 6, '.', '')
                ]);
            }else{
                $concepto1->addTraslado([
                    'Base' => number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto' => "002",
                    'TipoFactor' => "Exento",
                ]);
            }
            if($pardata[$i]->por_imp4 != 0)
            {
                $concepto1->addTraslado([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"003",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_imp4)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->ieps, 6, '.', '')
                ]);
            }
            if($pardata[$i]->por_imp2 != 0)
            {
                $concepto1->addRetencion([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"002",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_imp2)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->retiva, 6, '.', '')
                ]);
            }
            if($pardata[$i]->por_imp3 != 0)
            {
                $concepto1->addRetencion([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"001",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_imp3)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->retisr, 6, '.', '')
                ]);
            }

        }
        $creator->addSumasConceptos(null, 2);
        $creator->addSello($keyForFinkOk, $csdpass);
        $creator->moveSatDefinitionsToComprobante();
        $creator->saveXml($tmpxml);
        $cfdi = $creator->asXml();
        $resultado = $objConexion->operacion_timbrar($apikey, $cfdi);
        return $resultado;
    }

    public function TimbrarNota($factura,$receptor):string
    {
        $objConexion = new ConexionController($this->url);
        $tipodoc = 'N';
        //-------------------------------------------
        $datetime = new DateTime();
        $mex_zone = new DateTimeZone('America/Mexico_City');
        $datetime->setTimezone($mex_zone);
        $fecha = $datetime->format('Y-m-d');
        $hora = $datetime->format('H:i:s');
        $fechahora = $fecha.'T'.$hora;

        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $emidata = DB::table('datos_fiscales')->where('team_id',Filament::getTenant()->id)->first();
        $recdata = DB::table('clientes')->where('id',$receptor)->get();
        $facdata = DB::table('notade_creditos')->where('id',$factura)->get();
        $esquema = DB::table('esquemasimps')->where('id',$facdata[0]->esquema)->first();
        $pardata = DB::table('partidas_notade_creditos')->where('notade_credito_id',$factura)->get();
        $nopardata = count($pardata);
        $tido = "E";
        $csdpass = $emidata->csdpass;
        $apikey = $this->api_key;
        $cerFile = public_path('storage/'.$emidata->cer);
        $keyFile = public_path('storage/'.$emidata->key);
        $keyPEM = public_path('storage/'.$emidata->rfc.'.key.pem');
        $tmpxml =public_path('storage/TMPXMLFiles/'.$recdata[0]->rfc.'.xml');
        if (file_exists($keyPEM)) {
            unlink($keyPEM);
        }
        if (file_exists($tmpxml)) {
            unlink($tmpxml);
        }
        //-------------------------------------------------------        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        //dd($keyFile, $csdpass, $keyPEM, $csdpass);
        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        $keyForFinkOk = $openssl->pemKeyProtectOut($keyPEM, $csdpass, $csdpass);
        //-------------------------------------------
        $certificado = new \CfdiUtils\Certificado\Certificado($cerFile);
        $tipo_cambio = 1;
        if($facdata[0]->moneda != 'MXN')$tipo_cambio = number_format($facdata[0]->tcambio,4);
        $comprobanteAtributos = [
            'Serie' => $facdata[0]->serie ?? 'A',
            'Folio' => $facdata[0]->folio,
            'CondicionesDePago'=>$facdata[0]->condiciones ?? "CONTADO",
            'SubTotal'=>$facdata[0]->subtotal,
            'Moneda'=>$facdata[0]->moneda,
            'TipoCambio'=>$tipo_cambio,
            'Total'=>$facdata[0]->total,
            'TipoDeComprobante'=>$tido,
            'Exportacion'=>"01",
            'MetodoPago'=>$facdata[0]->forma,
            'LugarExpedicion'=>$emidata->codigo,
            'Fecha'=>$fechahora,
            'FormaPago'=>$facdata[0]->metodo
        ];

        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();
        $comprobante->addEmisor([
            'Rfc'=>$emidata->rfc,
            'Nombre'=>$emidata->nombre,
            'RegimenFiscal'=>$emidata->regimen
        ]);
        $comprobante->addReceptor([
            'Rfc'=>$recdata[0]->rfc,
            'Nombre'=>$recdata[0]->nombre,
            'RegimenFiscalReceptor'=>$recdata[0]->regimen,
            'DomicilioFiscalReceptor'=>$recdata[0]->codigo,
            'UsoCFDI'=>$facdata[0]->uso
        ]);
        if(count(DoctosRelacionados::where('docto_type','N')->where('docto_id',$facdata[0]->id)->get())>0){
            $doctorel = DoctosRelacionados::where('docto_type','N')->where('docto_id',$facdata[0]->id)->first();
            //dd($doctorel);
            $facrel = Facturas::where('id',$doctorel->rel_id)->first();
            $rel01 = $comprobante->addCfdiRelacionados(['TipoRelacion'=>$doctorel->rel_cause]);
            $rel01->addCfdiRelacionado([
                'UUID'=>$facrel->uuid
            ]);
        }


        for($i=0;$i<$nopardata;$i++)
        {
            $concepto1 = $comprobante->addConcepto([
                'ClaveProdServ'=>$pardata[$i]->cvesat,
                'Cantidad'=>number_format($pardata[$i]->cant, 2, '.', ''),
                'ClaveUnidad'=>$pardata[$i]->unidad,
                'ObjetoImp'=>"02",
                'Descripcion'=>$pardata[$i]->descripcion.' '.$pardata[$i]->observa ?? '',
                'ValorUnitario'=>number_format($pardata[$i]->precio, 6, '.', ''),
                'Importe'=>number_format($pardata[$i]->subtotal, 6, '.', '')
            ]);
            if($esquema->exento == 'NO') {
                $concepto1->addTraslado([
                    'Base' => number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto' => "002",
                    'TipoFactor' => "Tasa",
                    'TasaOCuota' => number_format(floatval($pardata[$i]->por_imp1) * 0.01, 6, '.', ''),
                    'Importe' => number_format($pardata[$i]->iva, 6, '.', '')
                ]);
            }else{
                $concepto1->addTraslado([
                    'Base' => number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto' => "002",
                    'TipoFactor' => "Exento",
                ]);
            }
            if($pardata[$i]->por_imp4 != 0)
            {
                $concepto1->addTraslado([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"003",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_imp4)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->ieps, 6, '.', '')
                ]);
            }
            if($pardata[$i]->por_imp2 != 0)
            {
                $concepto1->addRetencion([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"002",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_imp2)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->retiva, 6, '.', '')
                ]);
            }
            if($pardata[$i]->por_imp3 != 0)
            {
                $concepto1->addRetencion([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"001",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_imp3)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->retisr, 6, '.', '')
                ]);
            }

        }
        $creator->addSumasConceptos(null, 2);
        $creator->addSello($keyForFinkOk, $csdpass);
        $creator->moveSatDefinitionsToComprobante();
        $creator->saveXml($tmpxml);
        $cfdi = $creator->asXml();
        $resultado = $objConexion->operacion_timbrar($apikey, $cfdi);
        return $resultado;
    }
    public function TimbrarPagos($factura,$emisor,$receptor):string
    {

        $objConexion = new ConexionController($this->url);
        //-------------------------------------------
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $datetime = new DateTime();
        $mex_zone = new DateTimeZone('America/Mexico_City');
        $datetime->setTimezone($mex_zone);
        $fecha = $datetime->format('Y-m-d');
        $hora = $datetime->format('H:i:s');
        $fechahora = $fecha . 'T' . $hora;

        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $emidata = DB::table('datos_fiscales')->where('id', $emisor)->first();
        $recdata = DB::table('clientes')->where('id', $receptor)->get();
        $facdata = DB::table('pagos')->where('id', $factura)->get();
        $pardata = DB::table('par_pagos')->where('pagos_id', $factura)->get();
        $fac_id = $pardata[0]->uuidrel;
        $antdata = DB::table('facturas')->where('id', $fac_id)->get();
        $tido = "P";
        $csdpass = $emidata->csdpass;
        $apikey = $this->api_key;
        $cerFile = public_path('storage/' . $emidata->cer);
        $keyFile = public_path('storage/' . $emidata->key);
        $keyPEM = public_path('storage/' . $emidata->rfc . '.key.pem');
        $tmpxml = public_path('storage/TMPXMLFiles/' . $recdata[0]->rfc . '.xml');

        $fecha2 = Carbon::create($facdata[0]->fechapago)->format('Y-m-d');
        $fechahora2 = $fecha2 . 'T' . $hora;
        if (file_exists($keyPEM)) {
            unlink($keyPEM);
        }
        if (file_exists($tmpxml)) {
            unlink($tmpxml);
        }
        //-------------------------------------------------------
        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        $keyForFinkOk = $openssl->pemKeyProtectOut($keyPEM, $csdpass, $csdpass);
        //-------------------------------------------
        $serie_d = 'P';
        $certificado = new \CfdiUtils\Certificado\Certificado($cerFile);
        $moneda_apli = 'MXN';
        $tipo_cambio = 1;
        if ($facdata[0]->moneda != 'XXX') {
            $tipo_cambio = number_format($facdata[0]->tcambio, 4);
            $moneda_apli = 'USD';
        }

        $comprobanteAtributos = [
            'Serie' => $serie_d,
            'Folio' => $facdata[0]->folio,
            'SubTotal' => 0,
            'Moneda' => "XXX",
            'Total' => 0,
            'TipoDeComprobante' => $tido,
            'Exportacion' => "01",
            'LugarExpedicion' => $emidata->codigo,
            'Fecha' => $fechahora
        ];

        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();
        $comprobante->addEmisor([
            'Rfc' => $emidata->rfc,
            'Nombre' => $emidata->nombre,
            'RegimenFiscal' => $emidata->regimen
        ]);
        $comprobante->addReceptor([
            'Rfc' => $recdata[0]->rfc,
            'Nombre' => $recdata[0]->nombre,
            'RegimenFiscalReceptor' => $recdata[0]->regimen,
            'DomicilioFiscalReceptor' => $recdata[0]->codigo,
            'UsoCFDI' => $facdata[0]->usocfdi
        ]);

        $comprobante->addConcepto([
            'ClaveProdServ' => '84111506',
            'Cantidad' => 1,
            'ClaveUnidad' => 'ACT',
            'ObjetoImp' => "01",
            'Descripcion' => 'Pago',
            'ValorUnitario' => 0,
            'Importe' => 0
        ]);

        $Pagos = new \CfdiUtils\Elements\Pagos20\Pagos();
        $Pagos->addTotales([
            'TotalTrasladosBaseIVA16' => $facdata[0]->subtotal,
            'TotalTrasladosImpuestoIVA16' => $facdata[0]->iva,
            'MontoTotalPagos' => $facdata[0]->total
        ]);
        $equivalencia = 1;
        if ($facdata[0]->moneda != $antdata[0]->moneda) {
            if ($antdata[0]->moneda == 'USD' && $facdata[0]->moneda == 'MXN') $equivalencia = number_format($facdata[0]->tcambio, 4);
            if ($antdata[0]->moneda == 'MXN' && $facdata[0]->moneda == 'USD') {
                $equivalencia = number_format(1 / $facdata[0]->tcambio, 4);
            }

        } else {
            $equivalencia = intval('1');
        }
        foreach ($pardata as $pdata)
        {
            $facrel = Facturas::where('id', $pdata->uuidrel)->first();
            $Pagos->addPago([
                'FechaPago' => $fechahora2,
                'FormaDePagoP' => $facdata[0]->forma,
                'MonedaP' => $moneda_apli,
                'TipoCambioP' => $tipo_cambio,
                'Monto' => round(floatval($pdata->imppagado),6)
            ])->addDoctoRelacionado([
                'IdDocumento' => $facrel->uuid,
                'MonedaDR' => $facrel->moneda,
                'EquivalenciaDR' => bcdiv(floatval($pdata->equivalencia),1,4),
                'NumParcialidad' => intval($pdata->parcialidad),
                'ImpSaldoAnt' => bcdiv(round(floatval($pdata->saldoant),6),1,2),
                'ImpPagado' => bcdiv(floatval($pdata->imppagado),floatval($pdata->equivalencia),2),
                'ImpSaldoInsoluto' => bcdiv(round(floatval($pdata->insoluto),6),1,2),
                'ObjetoImpDR' => "02"
            ])->addImpuestosDR()
                ->addTrasladosDR()
                ->addTrasladoDR([
                    'BaseDR' => round(floatval($pdata->imppagado) / 1.16,6),
                    'ImpuestoDR' => "002",
                    'TipoFactorDR' => "Tasa",
                    'TasaOCuotaDR' => "0.160000",
                    'ImporteDR' => round((floatval($pdata->imppagado) / 1.16) * 0.16,6)
                ]);
        }
        //dd($Pagos);
        PagosWriter::calculateAndPut($Pagos);
        $comprobante->addComplemento($Pagos);

        //$creator->addSumasConceptos(null, 2);
        $creator->addSello($keyForFinkOk, $csdpass);
        $creator->moveSatDefinitionsToComprobante();
        $creator->saveXml($tmpxml);
        $asserts = $creator->validate();
        if ($asserts->hasErrors()) {
            $var1 = $asserts->get('XSD01')->getExplanation();
            $ress = [
                'codigo'=>"300",
                'mensaje'=>$var1
            ];
            //return json_encode($ress);
        }
        $creator->saveXml($tmpxml);
        $cfdi = $creator->asXml();
        $resultado = $objConexion->operacion_timbrar($apikey, $cfdi);
        return $resultado;
    }

    public function TimbrarPagos_Uni($factura,$emisor,$receptor):string
    {

        $objConexion = new ConexionController($this->url);
        //-------------------------------------------
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $datetime = new DateTime();
        $mex_zone = new DateTimeZone('America/Mexico_City');
        $datetime->setTimezone($mex_zone);
        $fecha = $datetime->format('Y-m-d');
        $hora = $datetime->format('H:i:s');
        $fechahora = $fecha . 'T' . $hora;

        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $emidata = DB::table('datos_fiscales')->where('id', $emisor)->first();
        $recdata = DB::table('clientes')->where('id', $receptor)->get();
        $facdata = DB::table('pagos')->where('id', $factura)->get();
        $pardata = DB::table('par_pagos')->where('pagos_id', $factura)->get();
        $fac_id = $pardata[0]->uuidrel;
        $antdata = DB::table('facturas')->where('id', $fac_id)->get();
        $tido = "P";
        $csdpass = $emidata->csdpass;
        $apikey = $this->api_key;
        $cerFile = public_path('storage/' . $emidata->cer);
        $keyFile = public_path('storage/' . $emidata->key);
        $keyPEM = public_path('storage/' . $emidata->rfc . '.key.pem');
        $tmpxml = public_path('storage/TMPXMLFiles/' . $recdata[0]->rfc . '.xml');

        $fecha2 = Carbon::create($facdata[0]->fechapago)->format('Y-m-d');
        $fechahora2 = $fecha2 . 'T' . $hora;
        if (file_exists($keyPEM)) {
            unlink($keyPEM);
        }
        if (file_exists($tmpxml)) {
            unlink($tmpxml);
        }
        //-------------------------------------------------------
        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        $keyForFinkOk = $openssl->pemKeyProtectOut($keyPEM, $csdpass, $csdpass);
        //-------------------------------------------
        $serie_d = 'P';
        $certificado = new \CfdiUtils\Certificado\Certificado($cerFile);
        $moneda_apli = 'MXN';
        $tipo_cambio = 1;
        if ($facdata[0]->moneda != 'XXX') {
            $tipo_cambio = number_format($facdata[0]->tcambio, 4);
            $moneda_apli = 'USD';
        }

        $comprobanteAtributos = [
            'Serie' => $serie_d,
            'Folio' => $facdata[0]->folio,
            'SubTotal' => 0,
            'Moneda' => "XXX",
            'Total' => 0,
            'TipoDeComprobante' => $tido,
            'Exportacion' => "01",
            'LugarExpedicion' => $emidata->codigo,
            'Fecha' => $fechahora
        ];

        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();
        $comprobante->addEmisor([
            'Rfc' => $emidata->rfc,
            'Nombre' => $emidata->nombre,
            'RegimenFiscal' => $emidata->regimen
        ]);
        $comprobante->addReceptor([
            'Rfc' => $recdata[0]->rfc,
            'Nombre' => $recdata[0]->nombre,
            'RegimenFiscalReceptor' => $recdata[0]->regimen,
            'DomicilioFiscalReceptor' => $recdata[0]->codigo,
            'UsoCFDI' => $facdata[0]->usocfdi
        ]);

        $comprobante->addConcepto([
            'ClaveProdServ' => '84111506',
            'Cantidad' => 1,
            'ClaveUnidad' => 'ACT',
            'ObjetoImp' => "01",
            'Descripcion' => 'Pago',
            'ValorUnitario' => 0,
            'Importe' => 0
        ]);

        $Pagos = new \CfdiUtils\Elements\Pagos20\Pagos();
        $Pagos->addTotales([
            'TotalTrasladosBaseIVA16' => $facdata[0]->subtotal,
            'TotalTrasladosImpuestoIVA16' => $facdata[0]->iva,
            'MontoTotalPagos' => $facdata[0]->total
        ]);
        $equivalencia = 1;
        if ($facdata[0]->moneda != $antdata[0]->moneda) {
            if ($antdata[0]->moneda == 'USD' && $facdata[0]->moneda == 'MXN') $equivalencia = number_format($facdata[0]->tcambio, 4);
            if ($antdata[0]->moneda == 'MXN' && $facdata[0]->moneda == 'USD') {
                $equivalencia = number_format(1 / $facdata[0]->tcambio, 4);
            }

        } else {
            $equivalencia = intval('1');
        }
        $par_pagos = $Pagos->addPago([
            'FechaPago' => $fechahora2,
            'FormaDePagoP' => $facdata[0]->forma,
            'MonedaP' => $moneda_apli,
            'TipoCambioP' => $tipo_cambio,
            'Monto' => round(floatval($facdata[0]->total),6)
        ]);
        foreach ($pardata as $pdata)
        {
            $facrel = Facturas::where('id', $pdata->uuidrel)->first();
            $par_pagos->addDoctoRelacionado([
                'IdDocumento' => $facrel->uuid,
                'MonedaDR' => $facrel->moneda,
                'EquivalenciaDR' => $equivalencia,
                'NumParcialidad' => intval($pdata->parcialidad),
                'ImpSaldoAnt' => round(floatval($pdata->saldoant),6),
                'ImpPagado' => round(floatval($pdata->imppagado),6),
                'ImpSaldoInsoluto' => round(floatval($pdata->insoluto),6),
                'ObjetoImpDR' => "02"
            ])->addImpuestosDR()
                ->addTrasladosDR()
                ->addTrasladoDR([
                    'BaseDR' => round(floatval($pdata->imppagado) / 1.16,6),
                    'ImpuestoDR' => "002",
                    'TipoFactorDR' => "Tasa",
                    'TasaOCuotaDR' => "0.160000",
                    'ImporteDR' => round((floatval($pdata->imppagado) / 1.16) * 0.16,6)
                ]);
        }
        //dd($Pagos);
        PagosWriter::calculateAndPut($Pagos);
        $comprobante->addComplemento($Pagos);

        //$creator->addSumasConceptos(null, 2);
        $creator->addSello($keyForFinkOk, $csdpass);
        $creator->moveSatDefinitionsToComprobante();
        $creator->saveXml($tmpxml);
        $asserts = $creator->validate();
        if ($asserts->hasErrors()) {
            //dd($asserts);
            $var1 = $asserts->get('XSD01')->getExplanation();
            $ress = [
                'codigo'=>"300",
                'mensaje'=>$var1
            ];
            //return json_encode($ress);
        }
        $creator->saveXml($tmpxml);
        $cfdi = $creator->asXml();
        $resultado = $objConexion->operacion_timbrar($apikey, $cfdi);
        return $resultado;
    }
    public function actualiza_fac_tim($factura,$cfdi_con):string
    {
        $tipodoc = 'F';
        $idfactura = $factura;
        $datos_xml = $cfdi_con;
        $uuid = "";
        $cfdi = \CfdiUtils\Cfdi::newFromString($datos_xml);
        $complemento = $cfdi->getNode();
        $tfd = $complemento->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
        if (null === $tfd) {
            echo 'No existe el timbre fiscal digital';
        } else {
            $uuid= $tfd['UUID'];
        }
        if($tipodoc == "F"){
            DB::table('facturas')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "N"){
            DB::table('notascreds')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "P"){
            DB::table('pagos')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "R"){
            DB::table('retenciones')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "C"){
            DB::table('cartas')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "T"){
            DB::table('traslados')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        return $uuid;
    }

    public function actualiza_not_tim($factura,$cfdi_con):string
    {
        $tipodoc = 'N';
        $idfactura = $factura;
        $datos_xml = $cfdi_con;
        $uuid = "";
        $cfdi = \CfdiUtils\Cfdi::newFromString($datos_xml);
        $complemento = $cfdi->getNode();
        $tfd = $complemento->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
        if (null === $tfd) {
            echo 'No existe el timbre fiscal digital';
        } else {
            $uuid= $tfd['UUID'];
        }
        if($tipodoc == "F"){
            DB::table('facturas')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "N"){
            DB::table('notade_creditos')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "P"){
            DB::table('pagos')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "R"){
            DB::table('retenciones')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "C"){
            DB::table('cartas')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "T"){
            DB::table('traslados')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        return $uuid;
    }
    public function actualiza_pag_tim($factura,$cfdi_con):string
    {
        $tipodoc = 'P';
        $idfactura = $factura;
        $datos_xml = $cfdi_con;
        $uuid = "";
        $cfdi = \CfdiUtils\Cfdi::newFromString($datos_xml);
        $complemento = $cfdi->getNode();
        $tfd = $complemento->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
        if (null === $tfd) {
            echo 'No existe el timbre fiscal digital';
        } else {
            $uuid= $tfd['UUID'];
        }

        DB::table('pagos')->where('id',$idfactura)->update([
            'uuid'=>$uuid,
            'estado' => 'Timbrada'
        ]);

        return $uuid;
    }
    public function genera_pdf($factura):string
    {
        $xml = $factura;
        if (file_exists('output.pdf')) {
            unlink('output.pdf');
        }
        $xml = \PhpCfdi\CfdiCleaner\Cleaner::staticClean($xml);
        $comprobante = \CfdiUtils\Nodes\XmlNodeUtils::nodeFromXmlString($xml);
        $cfdiData = (new \PhpCfdi\CfdiToPdf\CfdiDataBuilder())->build($comprobante);
        $converter = new \PhpCfdi\CfdiToPdf\Converter(
            new \PhpCfdi\CfdiToPdf\Builders\Html2PdfBuilder()
        );
        $converter->createPdfAs($cfdiData, 'output.pdf');
        $datos_cfdi = base64_encode(file_get_contents('output.pdf'));
        return $datos_cfdi;
    }

}
