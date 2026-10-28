<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\NominaSemanal;
use App\Models\PeriodoNomina;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\GestionarPeriodoNominaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PeriodoNominaActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_regenera_un_periodo_generado_y_recalcula_sus_detalles(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);
        Empleado::create([
            'numero_empleado' => 'NOM-001',
            'nombre' => 'Empleado',
            'apellido_paterno' => 'Nómina',
            'sucursal_id' => $sucursal->id,
            'fecha_ingreso' => '2026-10-01',
            'estatus' => 'activo',
        ]);
        $periodo = PeriodoNomina::create([
            'fecha_inicio' => '2026-10-19',
            'fecha_fin' => '2026-10-25',
            'estatus' => 'generada',
        ]);
        $usuario = User::factory()->create();

        $resultado = app(GestionarPeriodoNominaService::class)->regenerar($periodo, $usuario->id);

        $this->assertSame('generada', $resultado->estatus);
        $this->assertDatabaseCount('nominas_semanales', 1);
    }

    public function test_elimina_un_periodo_no_cerrado_y_sus_detalles(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);
        $empleado = Empleado::create([
            'numero_empleado' => 'NOM-002',
            'nombre' => 'Empleado',
            'apellido_paterno' => 'Eliminar',
            'sucursal_id' => $sucursal->id,
            'fecha_ingreso' => '2026-10-01',
            'estatus' => 'activo',
        ]);
        $periodo = PeriodoNomina::create([
            'fecha_inicio' => '2026-10-26',
            'fecha_fin' => '2026-11-01',
            'estatus' => 'generada',
        ]);
        NominaSemanal::create([
            'periodo_nomina_id' => $periodo->id,
            'empleado_id' => $empleado->id,
        ]);

        app(GestionarPeriodoNominaService::class)->eliminar($periodo);

        $this->assertDatabaseMissing('periodos_nomina', ['id' => $periodo->id]);
        $this->assertDatabaseCount('nominas_semanales', 0);
    }

    public function test_no_elimina_un_periodo_cerrado(): void
    {
        $periodo = PeriodoNomina::create([
            'fecha_inicio' => '2026-11-02',
            'fecha_fin' => '2026-11-08',
            'estatus' => 'cerrada',
        ]);

        $this->expectException(ValidationException::class);

        app(GestionarPeriodoNominaService::class)->eliminar($periodo);
    }
}
