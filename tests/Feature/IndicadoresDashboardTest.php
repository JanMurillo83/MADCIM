<?php

namespace Tests\Feature;

use App\Filament\Widgets\IndicadoresDashboard;
use App\Models\CajaMovimiento;
use App\Models\NotasVentaRenta;
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

    public function test_rentas_del_mes_excluye_deposito_y_depositos_del_mes_considera_devoluciones(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-03 10:00:00'));

        $admin = User::factory()->create([
            'role' => 'Administrador',
        ]);

        $this->actingAs($admin);

        NotasVentaRenta::create([
            'serie' => 'NR',
            'folio' => '1',
            'fecha_emision' => now(),
            'estatus' => 'Pagada',
            'deposito' => 500,
            'subtotal' => 0,
            'impuestos_total' => 0,
            'total' => 1500,
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

        $this->assertSame('$1,000.00', $this->findStatValue($stats, 'Rentas del mes'));
        $this->assertSame('$200.00', $this->findStatValue($stats, 'Depósitos del mes'));
    }
}
