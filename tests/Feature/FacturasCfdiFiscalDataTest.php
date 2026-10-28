<?php

namespace Tests\Feature;

use App\Models\Clientes;
use App\Models\Configuracion;
use App\Models\FacturasCfdi;
use App\Services\FacturaCfdiDatosFiscales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FacturasCfdiFiscalDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_sat_catalogs_include_all_invoice_form_options(): void
    {
        $this->assertEqualsCanonicalizing(
            [
                'G01', 'G02', 'G03', 'I01', 'I02', 'I03', 'I04', 'I05', 'I06', 'I07', 'I08',
                'D01', 'D02', 'D03', 'D04', 'D05', 'D06', 'D07', 'D08', 'D09', 'D10',
                'S01', 'CP01', 'CN01',
            ],
            DB::table('sat_uso_cfdi')->pluck('clave')->all()
        );
        $this->assertEqualsCanonicalizing(
            [
                '01', '02', '03', '04', '05', '06', '08', '12', '13', '14', '15', '17',
                '23', '24', '25', '26', '27', '28', '29', '30', '31', '99',
            ],
            DB::table('sat_forma_pago')->pluck('clave')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['PUE', 'PPD'],
            DB::table('sat_metodo_pago')->pluck('clave')->all()
        );
        $this->assertEqualsCanonicalizing(
            [
                '601', '603', '605', '606', '607', '608', '610', '611', '612', '614',
                '615', '616', '620', '621', '622', '623', '624', '625', '626',
            ],
            DB::table('sat_regimen_fiscal')->pluck('clave')->all()
        );
    }

    public function test_factura_se_guarda_con_uso_cfdi_s01_y_pagos_por_comprobante(): void
    {
        $factura = FacturasCfdi::query()->create([
            'uso_cfdi' => 'S01',
            'forma_pago' => '03',
            'metodo_pago' => 'PUE',
            'regimen_fiscal_receptor' => '616',
            'lugar_expedicion' => '99999',
            'domicilio_fiscal_receptor' => '88888',
        ]);

        $this->assertDatabaseHas('facturas_cfdi', [
            'id' => $factura->id,
            'uso_cfdi' => 'S01',
            'forma_pago' => '03',
            'metodo_pago' => 'PUE',
            'regimen_fiscal_receptor' => '616',
            'lugar_expedicion' => '99999',
            'domicilio_fiscal_receptor' => '88888',
        ]);
    }

    public function test_fiscal_data_comes_from_configuration_and_selected_customer(): void
    {
        $configuracion = new Configuracion([
            'razon_social' => 'Emisor de prueba',
            'rfc' => 'AAA010101AAA',
            'regimen' => '601',
            'codigo' => '01000',
        ]);
        $cliente = new Clientes([
            'nombre' => 'Receptor de prueba',
            'rfc' => 'BBB010101BBB',
            'regimen' => '616',
            'codigo' => '64000',
        ]);

        $datosFiscales = new FacturaCfdiDatosFiscales;

        $this->assertSame([
            'nombre_emisor' => 'Emisor de prueba',
            'rfc_emisor' => 'AAA010101AAA',
            'regimen_fiscal_emisor' => '601',
            'lugar_expedicion' => '01000',
        ], $datosFiscales->datosEmisor($configuracion));

        $this->assertSame([
            'razon_social_receptor' => 'Receptor de prueba',
            'rfc_receptor' => 'BBB010101BBB',
            'regimen_fiscal_receptor' => '616',
            'domicilio_fiscal_receptor' => '64000',
        ], $datosFiscales->datosReceptor($cliente));
    }
}
