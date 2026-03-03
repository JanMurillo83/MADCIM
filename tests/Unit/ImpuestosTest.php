<?php

namespace Tests\Unit;

use App\Support\Impuestos;
use PHPUnit\Framework\TestCase;

class ImpuestosTest extends TestCase
{
    public function test_desglosar_iva_incluido_para_importe_redondo(): void
    {
        $desglose = Impuestos::desglosarIvaIncluido(116.00);

        $this->assertSame(100.00, $desglose['subtotal']);
        $this->assertSame(16.00, $desglose['iva']);
    }

    public function test_desglosar_iva_incluido_redondea_y_conserva_total(): void
    {
        $desglose = Impuestos::desglosarIvaIncluido(1.00);

        $this->assertSame(0.86, $desglose['subtotal']);
        $this->assertSame(0.14, $desglose['iva']);
        $this->assertSame(1.00, round($desglose['subtotal'] + $desglose['iva'], 2));
    }

    public function test_desglosar_iva_incluido_con_importe_cero_o_negativo_regresa_ceros(): void
    {
        $this->assertSame(['subtotal' => 0.0, 'iva' => 0.0], Impuestos::desglosarIvaIncluido(0));
        $this->assertSame(['subtotal' => 0.0, 'iva' => 0.0], Impuestos::desglosarIvaIncluido(-10));
    }
}
