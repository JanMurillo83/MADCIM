<?php

namespace Tests\Feature;

use App\Filament\Widgets\IndicadoresDashboard;
use App\Models\CajaMovimiento;
use App\Models\NotaVentaRentaPartidas;
use App\Models\NotasVentaRenta;
use App\Models\Productos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IndicadoresDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function findStatValue(array $stats, string $label): ?string
    {
        foreach ($stats as $stat) {
            if ((string) $stat->getLabel() === $label) {
                return (string) $stat->getValue();
            }
        }

        return null;
    }

    public function test_rentas_madera_y_equipo_del_mes_separadas_y_depositos_neto_del_mes_considera_devoluciones(): void
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

        $this->assertSame('$1,000.00', $this->findStatValue($stats, 'Renta de Madera del mes'));
        $this->assertSame('$500.00', $this->findStatValue($stats, 'Renta de Equipo del mes'));
        $this->assertSame('$200.00', $this->findStatValue($stats, 'Depósitos del mes'));
    }
}
