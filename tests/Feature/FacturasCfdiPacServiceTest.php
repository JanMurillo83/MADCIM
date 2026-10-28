<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\FacturaCfdiPartidas;
use App\Models\FacturasCfdi;
use App\Models\Productos;
use App\Services\FacturaCfdiPacService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacturasCfdiPacServiceTest extends TestCase
{
    public function test_timbrado_guarda_el_xml_y_los_datos_del_timbre_devuelto_por_prodigia(): void
    {
        $factura = $this->crearFactura();
        $this->configurarPac();
        $xmlTimbrado = $this->xmlTimbrado();
        Http::fake([
            'pac.test/timbrar' => Http::response([
                'code' => 200,
                'message' => 'Timbrado correcto',
                'data' => $xmlTimbrado,
            ]),
        ]);

        $resultado = app(FacturaCfdiPacService::class)->timbrar($factura);

        $this->assertTrue($resultado['success']);
        $this->assertStringContainsString('TEST-UUID', $resultado['message']);
        $factura->refresh();
        $this->assertSame('TEST-UUID', $factura->cfdi_uuid);
        $this->assertSame($xmlTimbrado, $factura->cfdi_xml);
        $this->assertSame('Vigente', $factura->cfdi_estatus_sat);
        $this->assertSame('Timbrada', $factura->estatus);
        $this->assertSame('S01', $factura->uso_cfdi);
        $this->assertSame('123456789', $factura->cfdi_no_certificado);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://pac.test/timbrar'
            && str_contains(base64_decode((string) $request->data()['xmlBase64']), '<cfdi:Comprobante')
            && $request->data()['modo_prueba'] === true);
    }

    public function test_tasa_iva_inferida_usa_el_valor_sat_y_recalcula_el_importe(): void
    {
        $factura = $this->crearFactura();
        $this->configurarPac();
        $partida = $factura->partidas()->firstOrFail();
        Productos::query()->findOrFail((int) $partida->item)->forceFill([
            'impuesto' => null,
            'tipo_factor' => null,
            'tasa_o_cuota' => null,
        ])->save();
        $partida->forceFill([
            'cantidad' => 1,
            'valor_unitario' => 2500,
            'subtotal' => 2155.17,
            'impuestos' => 344.83,
            'total' => 2500,
        ])->save();
        Log::spy();
        Http::fake([
            'pac.test/timbrar' => Http::response(['code' => 500, 'message' => 'Respuesta de prueba'], 500),
        ]);

        app(FacturaCfdiPacService::class)->timbrar($factura);

        Http::assertSent(function (Request $request): bool {
            $xml = base64_decode((string) $request->data()['xmlBase64'], true);

            if (! is_string($xml)) {
                return false;
            }

            $documento = new \DOMDocument;
            $this->assertTrue($documento->loadXML($xml));
            $xpath = new \DOMXPath($documento);
            $concepto = $xpath->query('//*[local-name()="Concepto"]')?->item(0);
            $traslado = $xpath->query('//*[local-name()="Traslado"]')?->item(0);
            $trasladoResumen = $xpath->query('//*[local-name()="Comprobante"]/*[local-name()="Impuestos"]/*[local-name()="Traslados"]/*[local-name()="Traslado"]')?->item(0);

            $this->assertSame('2155.170000', $concepto?->attributes?->getNamedItem('ValorUnitario')?->nodeValue);
            $this->assertSame('2155.17', $concepto?->attributes?->getNamedItem('Importe')?->nodeValue);
            $this->assertSame('0.160000', $traslado?->attributes?->getNamedItem('TasaOCuota')?->nodeValue);
            $this->assertSame('344.83', $traslado?->attributes?->getNamedItem('Importe')?->nodeValue);
            $this->assertSame('344.83', $trasladoResumen?->attributes?->getNamedItem('Importe')?->nodeValue);

            return true;
        });

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($factura): bool {
                $this->assertSame('No se pudo timbrar el CFDI.', $message);
                $this->assertSame($factura->id, $context['factura_cfdi_id']);
                $this->assertSame(500, $context['codigo_pac']);
                $this->assertTrue($context['xml_analizable']);
                $this->assertSame('MXN', $context['moneda']);
                $this->assertSame([2], $context['concepto_traslado_importe_decimales']);
                $this->assertSame([2], $context['resumen_traslado_importe_decimales']);
            $this->assertSame([2], $context['resumen_traslado_base_decimales']);
                $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $context['xml_sha256']);

                return true;
            });
    }

    public function test_suma_traslados_sin_redondear_cada_concepto_a_decimales_de_moneda(): void
    {
        $factura = $this->crearFactura();
        $this->configurarPac();
        $partidaOriginal = $factura->partidas()->firstOrFail();
        $atributosPartida = [
            'cantidad' => 1,
            'valor_unitario' => 0.03,
            'subtotal' => 0.03,
            'descuento' => 0,
            'impuestos' => 0.01,
            'total' => 0.04,
        ];
        $partidaOriginal->forceFill($atributosPartida)->save();
        FacturaCfdiPartidas::create([
            'factura_cfdi_id' => $factura->id,
            'item' => $partidaOriginal->item,
            'clave_prod_serv' => $partidaOriginal->clave_prod_serv,
            'clave_unidad' => $partidaOriginal->clave_unidad,
            'unidad' => $partidaOriginal->unidad,
            'descripcion' => 'Segundo producto CFDI',
            'objeto_imp' => '02',
            ...$atributosPartida,
        ]);
        Http::fake([
            'pac.test/timbrar' => Http::response(['code' => 500, 'message' => 'Respuesta de prueba'], 500),
        ]);

        app(FacturaCfdiPacService::class)->timbrar($factura);

        Http::assertSent(function (Request $request): bool {
            $xml = base64_decode((string) $request->data()['xmlBase64'], true);

            if (! is_string($xml)) {
                return false;
            }

            $documento = new \DOMDocument;
            $this->assertTrue($documento->loadXML($xml));
            $xpath = new \DOMXPath($documento);
            $trasladosConceptos = $xpath->query('//*[local-name()="Concepto"]/*[local-name()="Impuestos"]/*[local-name()="Traslados"]/*[local-name()="Traslado"]');
            $trasladoResumen = $xpath->query('//*[local-name()="Comprobante"]/*[local-name()="Impuestos"]/*[local-name()="Traslados"]/*[local-name()="Traslado"]')?->item(0);
            $impuestosComprobante = $xpath->query('/*[local-name()="Comprobante"]/*[local-name()="Impuestos"]')?->item(0);

            $this->assertSame(2, $trasladosConceptos?->length);
            foreach ($trasladosConceptos ?? [] as $trasladoConcepto) {
                $this->assertSame('0.00', $trasladoConcepto->attributes?->getNamedItem('Importe')?->nodeValue);
            }
            $this->assertSame('0.01', $trasladoResumen?->attributes?->getNamedItem('Importe')?->nodeValue);
            $this->assertSame('0.06', $trasladoResumen?->attributes?->getNamedItem('Base')?->nodeValue);
            $this->assertSame('0.01', $impuestosComprobante?->attributes?->getNamedItem('TotalImpuestosTrasladados')?->nodeValue);

            return true;
        });
    }

    public function test_timbrado_recupera_claves_sat_desde_producto_si_partida_no_guardo_los_valores(): void
    {
        $factura = $this->crearFactura();
        $this->configurarPac();
        $factura->partidas()->firstOrFail()->forceFill([
            'clave_prod_serv' => null,
            'clave_unidad' => null,
        ])->save();
        Http::fake([
            'pac.test/timbrar' => Http::response(['code' => 500, 'message' => 'Respuesta de prueba'], 500),
        ]);

        app(FacturaCfdiPacService::class)->timbrar($factura);

        Http::assertSent(function (Request $request): bool {
            $xml = base64_decode((string) $request->data()['xmlBase64'], true);

            if (! is_string($xml)) {
                return false;
            }

            $documento = new \DOMDocument;
            $this->assertTrue($documento->loadXML($xml));
            $xpath = new \DOMXPath($documento);
            $concepto = $xpath->query('//*[local-name()="Concepto"]')?->item(0);

            $this->assertSame('01010101', $concepto?->attributes?->getNamedItem('ClaveProdServ')?->nodeValue);
            $this->assertSame('H87', $concepto?->attributes?->getNamedItem('ClaveUnidad')?->nodeValue);

            return true;
        });
    }

    public function test_xml_usa_emisor_configurado_y_precision_de_moneda_para_los_importes_de_traslado(): void
    {
        $factura = $this->crearFactura();
        $this->configurarPac();
        $configuracion = Configuracion::query()->firstOrFail();
        $configuracion->fill([
            'razon_social' => 'Emisor Configurado SA de CV',
            'rfc' => 'AAA010101AAA',
            'regimen' => '601',
            'codigo' => '01000',
        ])->save();
        DB::table('sat_moneda')->insertOrIgnore([
            'clave' => 'JPY',
            'descripcion' => 'Yen japonés',
            'decimales' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $factura->forceFill([
            'rfc_emisor' => 'BBB010101BBB',
            'nombre_emisor' => 'Emisor Anterior',
            'regimen_fiscal_emisor' => '616',
            'moneda' => 'JPY',
        ])->save();
        $factura->partidas()->firstOrFail()->forceFill([
            'subtotal' => 100.49,
            'valor_unitario' => 100.49,
            'impuestos' => 16,
            'total' => 116.49,
        ])->save();
        Http::fake([
            'pac.test/timbrar' => Http::response(['code' => 500, 'message' => 'Respuesta de prueba'], 500),
        ]);

        app(FacturaCfdiPacService::class)->timbrar($factura);

        Http::assertSent(function (Request $request): bool {
            $xml = base64_decode((string) $request->data()['xmlBase64'], true);

            if (! is_string($xml)) {
                return false;
            }

            $documento = new \DOMDocument;
            $this->assertTrue($documento->loadXML($xml));
            $xpath = new \DOMXPath($documento);
            $comprobante = $xpath->query('/*[local-name()="Comprobante"]')?->item(0);
            $emisor = $xpath->query('//*[local-name()="Emisor"]')?->item(0);
            $receptor = $xpath->query('//*[local-name()="Receptor"]')?->item(0);
            $traslado = $xpath->query('//*[local-name()="Traslado"]')?->item(0);
            $trasladoResumen = $xpath->query('//*[local-name()="Comprobante"]/*[local-name()="Impuestos"]/*[local-name()="Traslados"]/*[local-name()="Traslado"]')?->item(0);

            $this->assertSame('JPY', $comprobante?->attributes?->getNamedItem('Moneda')?->nodeValue);
            $this->assertSame('100', $comprobante?->attributes?->getNamedItem('SubTotal')?->nodeValue);
            $this->assertSame('116', $comprobante?->attributes?->getNamedItem('Total')?->nodeValue);
            $this->assertSame('01000', $comprobante?->attributes?->getNamedItem('LugarExpedicion')?->nodeValue);
            $this->assertSame('AAA010101AAA', $emisor?->attributes?->getNamedItem('Rfc')?->nodeValue);
            $this->assertSame('EMISOR CONFIGURADO SA DE CV', $emisor?->attributes?->getNamedItem('Nombre')?->nodeValue);
            $this->assertSame('01000', $receptor?->attributes?->getNamedItem('DomicilioFiscalReceptor')?->nodeValue);
            $this->assertSame('16', $traslado?->attributes?->getNamedItem('Importe')?->nodeValue);
            $this->assertSame('16', $trasladoResumen?->attributes?->getNamedItem('Importe')?->nodeValue);
            $this->assertSame('100', $trasladoResumen?->attributes?->getNamedItem('Base')?->nodeValue);

            return true;
        });
    }

    public function test_un_timbrado_fallido_deja_la_factura_disponible_para_reintento(): void
    {
        $factura = $this->crearFactura();
        $this->configurarPac();
        Http::fake([
            'pac.test/timbrar' => Http::sequence()
                ->push(['code' => 500, 'message' => 'Error temporal'], 500)
                ->push([
                    'code' => 200,
                    'message' => 'Timbrado correcto',
                    'data' => $this->xmlTimbrado(),
                ]),
        ]);
        $servicio = app(FacturaCfdiPacService::class);

        $primerResultado = $servicio->timbrar($factura);
        $factura->refresh();
        $this->assertFalse($primerResultado['success']);
        $this->assertSame('Error de timbrado', $factura->cfdi_estatus_sat);
        $this->assertSame('Borrador', $factura->estatus);
        $this->assertNull($factura->cfdi_uuid);

        $segundoResultado = $servicio->timbrar($factura);

        $this->assertTrue($segundoResultado['success']);
        $this->assertSame('TEST-UUID', $factura->fresh()->cfdi_uuid);
    }

    public function test_factura_sin_timbre_puede_cancelarse_localmente_sin_contactar_al_pac(): void
    {
        $factura = $this->crearFactura();

        $resultado = app(FacturaCfdiPacService::class)->cancelarSinTimbrar($factura);

        $this->assertTrue($resultado['success']);
        $this->assertStringContainsString('localmente', $resultado['message']);
        $this->assertSame('Cancelada', $factura->fresh()->estatus);
        $this->assertEquals(0, $factura->fresh()->saldo_pendiente);
        $this->assertNull($factura->fresh()->cfdi_uuid);
        $this->assertNull($factura->fresh()->cfdi_fecha_cancelacion);
        Http::assertNothingSent();
    }

    public function test_aceptar_la_solicitud_de_cancelacion_no_marca_el_cfdi_como_cancelado(): void
    {
        $factura = $this->crearFactura();
        $factura->forceFill([
            'cfdi_uuid' => 'TEST-UUID',
            'estatus' => 'Timbrada',
            'cfdi_estatus_sat' => 'Vigente',
        ])->save();
        $this->configurarPac();
        Http::fake([
            'pac.test/cancelar*' => Http::response([
                'statusOk' => true,
                'message' => 'Solicitud aceptada',
                'data' => ['acuse' => '<acuse/>'],
            ]),
        ]);

        $servicio = app(FacturaCfdiPacService::class);
        $resultadoSinFolio = $servicio->cancelar($factura, '01');
        $this->assertFalse($resultadoSinFolio['success']);

        $resultado = $servicio->cancelar($factura, '02');

        $this->assertTrue($resultado['success']);
        $this->assertStringContainsString('pendiente de confirmación', $resultado['message']);
        $factura->refresh();
        $this->assertSame('02', $factura->cfdi_motivo_cancelacion);
        $this->assertSame('Solicitud enviada', $factura->cfdi_estatus_cancelacion);
        $this->assertSame('Vigente', $factura->cfdi_estatus_sat);
        $this->assertSame('Timbrada', $factura->estatus);
        $this->assertNull($factura->cfdi_fecha_cancelacion);
    }

    private function crearFactura(): FacturasCfdi
    {
        $this->crearCatalogosSat();
        $clienteId = DB::table('clientes')->insertGetId([
            'clave' => 'CLI-PAC-1',
            'nombre' => 'Cliente CFDI',
            'rfc' => 'XAXX010101000',
            'regimen' => '616',
            'codigo' => '01000',
            'calle' => 'Calle prueba',
            'exterior' => '1',
            'interior' => '',
            'colonia' => 'Centro',
            'municipio' => 'Ciudad',
            'estado' => 'Estado',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'cliente@example.test',
            'contacto' => 'Contacto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $producto = Productos::create([
            'clave' => 'PROD-PAC-1',
            'descripcion' => 'Producto CFDI',
            'grupo' => 'Grupo',
            'linea' => 'Linea',
            'clave_prod_serv' => '01010101',
            'clave_unidad' => 'H87',
            'unidad_sat' => 'Pieza',
            'objeto_imp' => '02',
            'impuesto' => '002',
            'tipo_factor' => 'Tasa',
            'tasa_o_cuota' => 0.16,
        ]);
        $factura = FacturasCfdi::create([
            'cliente_id' => $clienteId,
            'serie' => 'F',
            'folio' => '100',
            'fecha_emision' => now()->subMinutes(3),
            'moneda' => 'MXN',
            'tipo_cambio' => 1,
            'subtotal' => 100,
            'impuestos_total' => 16,
            'total' => 116,
            'tipo_comprobante' => 'I',
            'exportacion' => '01',
            'estatus' => 'Activa',
            'uso_cfdi' => 'G01',
            'forma_pago' => '01',
            'metodo_pago' => 'PUE',
            'rfc_emisor' => 'AAA010101AAA',
            'nombre_emisor' => 'Empresa Prueba',
            'regimen_fiscal_emisor' => '616',
            'rfc_receptor' => 'XAXX010101000',
            'razon_social_receptor' => 'Cliente CFDI',
            'domicilio_fiscal_receptor' => null,
            'regimen_fiscal_receptor' => '616',
        ]);
        FacturaCfdiPartidas::create([
            'factura_cfdi_id' => $factura->id,
            'cantidad' => 1,
            'item' => (string) $producto->id,
            'clave_prod_serv' => '01010101',
            'clave_unidad' => 'H87',
            'unidad' => 'Pieza',
            'descripcion' => 'Producto CFDI',
            'objeto_imp' => '02',
            'valor_unitario' => 100,
            'subtotal' => 100,
            'descuento' => 0,
            'impuestos' => 16,
            'total' => 116,
        ]);

        return $factura;
    }

    private function configurarPac(): void
    {
        Storage::fake('local');
        [$certificadoDer, $llavePem] = $this->crearCertificado();
        Storage::disk('local')->put('configuracion/prueba.cer', $certificadoDer);
        Storage::disk('local')->put('configuracion/prueba.key', $llavePem);

        $configuracion = Configuracion::query()->firstOrFail();
        $configuracion->fill([
            'sello_cer' => 'configuracion/prueba.cer',
            'sello_key' => 'configuracion/prueba.key',
            'sello_pass' => 'csd-secret',
            'pac_contract' => 'pac-contract',
            'pac_user' => 'pac-user',
            'pac_password' => 'pac-secret',
            'pac_modo_prueba' => true,
            'pac_timbrado_endpoint' => 'https://pac.test/timbrar',
            'pac_cancelacion_endpoint' => 'https://pac.test/cancelar',
        ]);
        $configuracion->save();
    }

    private function crearCatalogosSat(): void
    {
        DB::table('sat_tipo_comprobante')->insertOrIgnore([
            'clave' => 'I',
            'descripcion' => 'Ingreso',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sat_exportacion')->insertOrIgnore([
            'clave' => '01',
            'descripcion' => 'No aplica',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sat_clave_prod_serv')->insertOrIgnore([
            'clave' => '01010101',
            'descripcion' => 'No existe en el catálogo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sat_clave_unidad')->insertOrIgnore([
            'clave' => 'H87',
            'nombre' => 'Pieza',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sat_objeto_imp')->insertOrIgnore([
            'clave' => '02',
            'descripcion' => 'Sí objeto de impuesto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sat_impuesto')->insertOrIgnore([
            'clave' => '002',
            'descripcion' => 'IVA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sat_tipo_factor')->insertOrIgnore([
            'clave' => 'Tasa',
            'descripcion' => 'Tasa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{string, string}
     */
    private function crearCertificado(): array
    {
        $llave = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        $solicitud = openssl_csr_new(['commonName' => 'CSD de prueba'], $llave);
        $certificado = openssl_csr_sign($solicitud, null, $llave, 365, [], 123456789);
        openssl_x509_export($certificado, $certificadoPem);
        openssl_pkey_export($llave, $llavePem, 'csd-secret');
        $certificadoBase64 = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s/', '', $certificadoPem);
        $certificadoDer = base64_decode($certificadoBase64, true);

        $this->assertIsString($certificadoDer);

        return [$certificadoDer, $llavePem];
    }

    private function xmlTimbrado(): string
    {
        return '<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" NoCertificado="123456789" Certificado="dGVzdA==" Sello="sello">'
            .'<cfdi:Complemento><tfd:TimbreFiscalDigital xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" UUID="TEST-UUID" FechaTimbrado="2026-03-02T12:00:00"/></cfdi:Complemento>'
            .'</cfdi:Comprobante>';
    }
}
