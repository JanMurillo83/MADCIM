<?php

namespace Tests\Feature;

use App\Filament\Widgets\IndicadoresDashboard;
use App\Models\CajaMovimiento;
use App\Models\CierreDevolucionRenta;
use App\Models\ClienteDireccionEntrega;
use App\Models\Clientes;
use App\Models\NotaVentaRentaPartidas;
use App\Models\NotaEnvio;
use App\Models\NotasVentaRenta;
use App\Models\Productos;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IndicadoresDashboardTest extends TestCase
{
    use DatabaseTransactions;

    private function findStatValue(array $stats, string $label): ?string
    {
        foreach ($stats as $stat) {
            if ((string) $stat->getLabel() === $label) {
                return (string) $stat->getValue();
            }
        }

        return null;
    }

    public function test_rentas_madera_y_equipo_del_mes_separadas_y_depositos_totales_y_pendientes_consideran_devoluciones(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-03 10:00:00'));

        $admin = User::factory()->create([
            'role' => 'Administrador',
        ]);

        $this->actingAs($admin);

        $productoMadera = Productos::create([
            'clave' => 'MAD-001',
            'descripcion' => 'Tabla de madera',
            'grupo' => 'TABLA',
            'linea' => 'MADERA',
        ]);

        $productoEquipo = Productos::create([
            'clave' => 'EQ-001',
            'descripcion' => 'Andamio',
            'grupo' => 'ANDAMIO',
            'linea' => 'EQUIPO',
        ]);

        NotasVentaRenta::create([
            'serie' => 'NR',
            'folio' => '1',
            'fecha_emision' => now(),
            'estatus' => 'Pagada',
            'deposito' => 500,
            'subtotal' => 0,
            'impuestos_total' => 0,
            'total' => 2000,
        ]);

        NotaVentaRentaPartidas::create([
            'nota_venta_renta_id' => 1,
            'cantidad' => 1,
            'item' => (string) $productoMadera->id,
            'descripcion' => 'Renta madera',
            'valor_unitario' => 1000,
            'subtotal' => 862.07,
            'impuestos' => 137.93,
            'total' => 1000,
        ]);

        NotaVentaRentaPartidas::create([
            'nota_venta_renta_id' => 1,
            'cantidad' => 1,
            'item' => (string) $productoEquipo->id,
            'descripcion' => 'Renta equipo',
            'valor_unitario' => 500,
            'subtotal' => 431.03,
            'impuestos' => 68.97,
            'total' => 500,
        ]);

        // Egreso por devolución real del depósito
        CajaMovimiento::create([
            'caja_id' => 1,
            'tipo' => 'Egreso',
            'fuente' => 'Devolución depósito renta',
            'metodo_pago' => 'Efectivo',
            'importe' => 300,
            'fecha' => now(),
        ]);

        $widget = new class extends IndicadoresDashboard
        {
            public function stats(): array
            {
                return $this->getStats();
            }
        };

        $stats = $widget->stats();

        $this->assertSame('$1,000.00', $this->findStatValue($stats, 'Mensual | Renta Madera'));
        $this->assertSame('$500.00', $this->findStatValue($stats, 'Mensual | Renta Equipo'));
        $this->assertSame('$500.00', $this->findStatValue($stats, 'Mensual | Depósitos Totales'));
        $this->assertSame('$200.00', $this->findStatValue($stats, 'Mensual | Depósitos Pendientes de Devolver'));
        $this->assertSame('$500.00', $this->findStatValue($stats, 'Anual | Depósitos Totales'));
        $this->assertSame('$200.00', $this->findStatValue($stats, 'Anual | Depósitos Pendientes de Devolver'));
    }

    public function test_rentas_por_vencer_considera_fecha_de_vencimiento_de_partida(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 10:00:00'));

        $admin = User::factory()->create([
            'role' => 'Administrador',
        ]);

        $this->actingAs($admin);

        $producto = Productos::create([
            'clave' => 'EQ-VENCIMIENTO',
            'descripcion' => 'Equipo con vencimiento por partida',
            'grupo' => 'EQUIPO',
            'linea' => 'EQUIPO',
        ]);

        $nota = NotasVentaRenta::create([
            'serie' => 'NR',
            'folio' => '2',
            'fecha_emision' => now(),
            'estatus' => 'Activa',
            'subtotal' => 100,
            'impuestos_total' => 16,
            'total' => 116,
        ]);

        NotaEnvio::create([
            'nota_venta_renta_id' => $nota->id,
            'fecha_emision' => now()->toDateString(),
            'estatus' => 'Enviada',
        ]);

        $notaNoEnviada = NotasVentaRenta::create([
            'serie' => 'NR',
            'folio' => '3',
            'fecha_emision' => now(),
            'estatus' => 'Activa',
            'subtotal' => 100,
            'impuestos_total' => 16,
            'total' => 116,
        ]);
        NotaVentaRentaPartidas::create([
            'nota_venta_renta_id' => $notaNoEnviada->id,
            'cantidad' => 1,
            'item' => (string) $producto->id,
            'descripcion' => $producto->descripcion,
            'fecha_vencimiento' => now()->addDays(3)->toDateString(),
            'valor_unitario' => 100,
            'subtotal' => 100,
            'impuestos' => 16,
            'total' => 116,
        ]);

        NotaVentaRentaPartidas::create([
            'nota_venta_renta_id' => $nota->id,
            'cantidad' => 1,
            'item' => (string) $producto->id,
            'descripcion' => $producto->descripcion,
            'fecha_vencimiento' => now()->addDays(3)->toDateString(),
            'valor_unitario' => 100,
            'subtotal' => 100,
            'impuestos' => 16,
            'total' => 116,
        ]);

        $widget = new class extends IndicadoresDashboard
        {
            public function stats(): array
            {
                return $this->getStats();
            }
        };

        $this->assertSame('1', $this->findStatValue($widget->stats(), 'Rentas por vencer'));
    }

    public function test_depositos_pendientes_descuenta_deposito_aplicado_en_cierre(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 10:00:00'));

        $admin = User::factory()->create([
            'role' => 'Administrador',
        ]);
        $this->actingAs($admin);

        $cliente = Clientes::create([
            'clave' => 'CLI-DEPOSITO-INDICADOR',
            'nombre' => 'Cliente indicador depósitos',
            'rfc' => 'XAXX010101000',
            'folio_ine' => 'INE-INDICADOR-001',
            'regimen' => '601',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'CDMX',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'indicador@example.com',
            'contacto' => 'Contacto',
            'saldo' => 0,
        ]);
        $direccion = ClienteDireccionEntrega::create([
            'cliente_id' => $cliente->id,
            'nombre_direccion' => 'Obra indicador',
            'calle' => 'Obra',
            'numero_exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'CDMX',
            'codigo_postal' => '01000',
            'pais' => 'México',
            'activa' => true,
        ]);
        $nota = NotasVentaRenta::create([
            'cliente_id' => $cliente->id,
            'direccion_entrega_id' => $direccion->id,
            'fecha_emision' => now(),
            'estatus' => 'Activa',
            'deposito' => 500,
            'subtotal' => 0,
            'impuestos_total' => 0,
            'total' => 500,
        ]);

        CierreDevolucionRenta::create([
            'cliente_id' => $cliente->id,
            'direccion_entrega_id' => $direccion->id,
            'estatus' => 'PendienteCaja',
            'deposito_acumulado' => 500,
            'deposito_aplicado' => 200,
            'deposito_a_devolver' => 300,
            'total_faltantes' => 200,
            'saldo_por_cobrar' => 0,
            'cerrada_en' => now(),
        ]);

        $widget = new class extends IndicadoresDashboard
        {
            public function stats(): array
            {
                return $this->getStats();
            }
        };

        $stats = $widget->stats();

        $this->assertSame('$300.00', $this->findStatValue($stats, 'Mensual | Depósitos Pendientes de Devolver'));
        $this->assertSame('$300.00', $this->findStatValue($stats, 'Anual | Depósitos Pendientes de Devolver'));
    }
}
