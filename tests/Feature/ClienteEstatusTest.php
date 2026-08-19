<?php

namespace Tests\Feature;

use App\Models\Clientes;
use App\Models\NotasVentaRenta;
use DomainException;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClienteEstatusTest extends TestCase
{
    public function test_cliente_moroso_solo_permite_operaciones_de_contado(): void
    {
        $cliente = Clientes::create($this->datosCliente([
            'estatus_cliente' => Clientes::ESTATUS_MOROSO,
        ]));

        $this->assertTrue($cliente->puedeCrearNota('contado'));
        $this->assertFalse($cliente->puedeCrearNota('credito'));

        $this->expectException(DomainException::class);
        $cliente->validarCreacionNota('credito');
    }

    public function test_cliente_bloqueado_no_permite_notas(): void
    {
        $cliente = Clientes::create($this->datosCliente([
            'estatus_cliente' => Clientes::ESTATUS_BLOQUEADO,
        ]));

        $this->assertFalse($cliente->puedeCrearNota('contado'));
        $this->assertFalse($cliente->puedeCrearNota('credito'));

        $this->expectException(DomainException::class);
        $cliente->validarCreacionNota('contado');
    }

    public function test_cliente_bloqueado_se_desbloquea_con_saldo_cero_o_excepcion_discrecional(): void
    {
        $cliente = Clientes::create($this->datosCliente([
            'estatus_cliente' => Clientes::ESTATUS_BLOQUEADO,
            'saldo' => 100,
        ]));

        $this->expectException(DomainException::class);
        $cliente->desbloquear();

        $cliente->update(['desbloqueo_discrecional' => true]);
        $cliente->desbloquear();

        $this->assertSame(Clientes::ESTATUS_ACTIVO, $cliente->fresh()->estatus_cliente);
    }

    public function test_cuenta_de_credito_vencida_cambia_cliente_a_moroso(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19 10:00:00'));

        $cliente = Clientes::create($this->datosCliente([
            'dias_credito' => 15,
        ]));

        NotasVentaRenta::create([
            'cliente_id' => $cliente->id,
            'fecha_emision' => Carbon::parse('2026-07-01'),
            'condicion_pago' => 'credito',
            'fecha_vencimiento_pago' => Carbon::parse('2026-07-16'),
            'subtotal' => 100,
            'impuestos_total' => 16,
            'total' => 116,
            'saldo_pendiente' => 116,
            'estatus' => 'Activa',
        ]);

        $this->assertSame(Clientes::ESTATUS_MOROSO, $cliente->fresh()->estatus_cliente);
    }

    /** @return array<string, mixed> */
    private function datosCliente(array $overrides = []): array
    {
        return array_merge([
            'clave' => 'CLI-' . uniqid(),
            'nombre' => 'Cliente de prueba',
            'rfc' => 'XAXX010101000',
            'regimen' => '601',
            'codigo' => '01000',
            'calle' => 'Calle de prueba',
            'exterior' => '1',
            'interior' => '',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'CDMX',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'cliente@example.com',
            'descuento' => 0,
            'lista' => 1,
            'contacto' => 'Contacto',
            'dias_credito' => 0,
            'saldo' => 0,
        ], $overrides);
    }
}
