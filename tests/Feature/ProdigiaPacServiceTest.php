<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Services\ProdigiaPacService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProdigiaPacServiceTest extends TestCase
{
    public function test_configuracion_inicia_con_endpoints_de_prodigia_y_modo_de_prueba(): void
    {
        $configuracion = Configuracion::query()->firstOrFail();

        $this->assertSame(
            'https://timbrado.pade.mx/servicio/rest/timbrado40/timbrarConCSD',
            $configuracion->pac_timbrado_endpoint,
        );
        $this->assertSame(
            'https://timbrado.pade.mx/servicio/rest/integracion/cancelarCfdiConOpciones',
            $configuracion->pac_cancelacion_endpoint,
        );
        $this->assertTrue($configuracion->pac_modo_prueba);
    }

    public function test_timbrado_envia_credenciales_xml_csd_y_modo_de_prueba_a_prodigia(): void
    {
        $this->configurePac(['pac_modo_prueba' => true]);
        [$certificadoPath, $llavePath] = $this->createCsdFiles();

        try {
            Http::fake([
                'pac.test/timbrar' => Http::response([
                    'code' => 200,
                    'message' => 'Timbrado correcto',
                    'data' => '<cfdi:Comprobante><cfdi:Complemento><tfd:TimbreFiscalDigital UUID="test-uuid"/></cfdi:Complemento></cfdi:Comprobante>',
                ]),
            ]);

            $result = json_decode(
                app(ProdigiaPacService::class)->timbrar('<cfdi:Comprobante/>', $certificadoPath, $llavePath, 'csd-secret'),
                true,
            );

            $this->assertSame('timbrar', $result['operacion']);
            $this->assertStringContainsString('test-uuid', $result['cfdi']);
            Http::assertSent(fn (Request $request): bool => $request->url() === 'https://pac.test/timbrar'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('pac-user:pac-secret'))
                && $request->hasHeader('X-PAC-Contract', 'pac-contract')
                && $request->data()['xmlBase64'] === base64_encode('<cfdi:Comprobante/>')
                && $request->data()['certBase64'] === base64_encode('certificate')
                && $request->data()['keyBase64'] === base64_encode('private-key')
                && $request->data()['keyPass'] === 'csd-secret'
                && $request->data()['modo_prueba'] === true);
        } finally {
            $this->deleteCsdFiles($certificadoPath, $llavePath);
        }
    }

    public function test_timbrado_decodifica_el_cfdi_de_la_respuesta_xml_de_servicio_timbrado(): void
    {
        $this->configurePac();
        [$certificadoPath, $llavePath] = $this->createCsdFiles();
        $cfdi = '<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4"><cfdi:Complemento><tfd:TimbreFiscalDigital xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" UUID="test-uuid"/></cfdi:Complemento></cfdi:Comprobante>';
        $respuesta = '<servicioTimbrado><timbradoOk>true</timbradoOk><codigo>200</codigo><mensaje>Timbrado correcto</mensaje><xmlBase64>'.base64_encode($cfdi).'</xmlBase64></servicioTimbrado>';

        try {
            Http::fake([
                'pac.test/timbrar' => Http::response($respuesta, 200, ['Content-Type' => 'application/xml']),
            ]);

            $result = json_decode(
                app(ProdigiaPacService::class)->timbrar('<cfdi:Comprobante/>', $certificadoPath, $llavePath, 'csd-secret'),
                true,
            );

            $this->assertSame('200', $result['codigo']);
            $this->assertSame('Timbrado correcto', $result['mensaje']);
            $this->assertSame($cfdi, $result['cfdi']);
        } finally {
            $this->deleteCsdFiles($certificadoPath, $llavePath);
        }
    }

    public function test_timbrado_informa_el_error_devuelto_en_la_respuesta_xml_de_servicio_timbrado(): void
    {
        $this->configurePac();
        [$certificadoPath, $llavePath] = $this->createCsdFiles();
        $respuesta = '<servicioTimbrado><timbradoOk>false</timbradoOk><codigo>305</codigo><mensaje>Certificado no válido</mensaje></servicioTimbrado>';

        try {
            Http::fake([
                'pac.test/timbrar' => Http::response($respuesta, 200, ['Content-Type' => 'application/xml']),
            ]);

            $result = json_decode(
                app(ProdigiaPacService::class)->timbrar('<cfdi:Comprobante/>', $certificadoPath, $llavePath, 'csd-secret'),
                true,
            );

            $this->assertSame('305', $result['codigo']);
            $this->assertSame('Certificado no válido', $result['mensaje']);
            $this->assertNull($result['cfdi']);
        } finally {
            $this->deleteCsdFiles($certificadoPath, $llavePath);
        }
    }

    public function test_cancelacion_envia_uuid_motivo_csd_y_modo_productivo_al_endpoint_configurado(): void
    {
        $this->configurePac(['pac_modo_prueba' => false]);
        [$certificadoPath, $llavePath] = $this->createCsdFiles();

        try {
            Http::fake([
                'pac.test/cancelar*' => Http::response([
                    'statusOk' => true,
                    'message' => 'Cancelación aceptada',
                    'data' => ['acuse' => '<acuse/>'],
                ]),
            ]);

            $result = json_decode(
                app(ProdigiaPacService::class)->cancelar(
                    $llavePath,
                    $certificadoPath,
                    'csd-secret',
                    'test-uuid',
                    'AAA010101AAA',
                    '01',
                    'replacement-uuid',
                ),
                true,
            );

            $this->assertSame('success', $result['resultado']);
            $this->assertSame('<acuse/>', $result['acuse']);
            Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://pac.test/cancelar')
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('pac-user:pac-secret'))
                && $request->data()['uuids'] === 'TEST-UUID|01|REPLACEMENT-UUID'
                && $request->data()['modo_prueba'] === false
                && $request->data()['certBase64'] === base64_encode('certificate')
                && $request->data()['keyBase64'] === base64_encode('private-key'));
        } finally {
            $this->deleteCsdFiles($certificadoPath, $llavePath);
        }
    }

    public function test_la_contrasena_pac_se_guarda_encriptada_y_no_se_expone_en_el_modelo(): void
    {
        $configuracion = $this->configurePac();
        $configuracion->refresh();

        $this->assertSame('pac-secret', $configuracion->pac_password);
        $this->assertNotSame('pac-secret', $configuracion->getRawOriginal('pac_password'));
        $this->assertContains('pac_password', $configuracion->getHidden());
    }

    private function configurePac(array $overrides = []): Configuracion
    {
        $configuracion = Configuracion::query()->firstOrFail();
        $configuracion->fill(array_merge([
            'pac_contract' => 'pac-contract',
            'pac_user' => 'pac-user',
            'pac_password' => 'pac-secret',
            'pac_modo_prueba' => true,
            'pac_timbrado_endpoint' => 'https://pac.test/timbrar',
            'pac_cancelacion_endpoint' => 'https://pac.test/cancelar',
        ], $overrides));
        $configuracion->save();

        return $configuracion;
    }

    private function createCsdFiles(): array
    {
        $certificadoPath = tempnam(sys_get_temp_dir(), 'pac-cer-');
        $llavePath = tempnam(sys_get_temp_dir(), 'pac-key-');

        file_put_contents($certificadoPath, 'certificate');
        file_put_contents($llavePath, 'private-key');

        return [$certificadoPath, $llavePath];
    }

    private function deleteCsdFiles(string $certificadoPath, string $llavePath): void
    {
        unlink($certificadoPath);
        unlink($llavePath);
    }
}
