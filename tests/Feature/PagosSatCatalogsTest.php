<?php

namespace Tests\Feature;

use App\Models\Clientes;
use App\Models\Pagos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PagosSatCatalogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_puede_registrar_un_pago_con_defaults_de_cfdi_y_llaves_foraneas_sat(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('secret'),
            'role' => 'Administrador',
            'sucursal_id' => null,
        ]);

        $cliente = Clientes::create([
            'clave' => 'C-001',
            'nombre' => 'Cliente Test',
            'rfc' => 'XAXX010101000',
            'regimen' => '616',
            'codigo' => '64000',
            'calle' => 'Calle 1',
            'exterior' => '1',
            'interior' => 'A',
            'colonia' => 'Centro',
            'municipio' => 'Monterrey',
            'estado' => 'NL',
            'pais' => 'MEX',
            'telefono' => '8112345678',
            'correo' => 'cliente@test.com',
            'descuento' => 0,
            'lista' => 1,
            'contacto' => 'Contacto',
            'dias_credito' => 0,
            'saldo' => 0,
        ]);

        $pago = Pagos::create([
            'documento_tipo' => 'notas_venta_renta',
            'documento_id' => 1,
            'cliente_id' => $cliente->id,
            'fecha_pago' => now(),
            'forma_pago' => '01',
            'importe' => 18000,
            'user_id' => $user->id,
            'observaciones' => null,
        ]);

        $this->assertDatabaseHas('pagos', [
            'id' => $pago->id,
            'tipo_comprobante' => 'P',
            'moneda' => 'MXN',
            'exportacion' => '01',
            'uso_cfdi' => 'CP01',
        ]);
    }
}
