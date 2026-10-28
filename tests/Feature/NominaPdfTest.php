<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\NominaSemanal;
use App\Models\PeriodoNomina;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NominaPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_descarga_el_pdf_de_la_nomina_fuera_de_livewire(): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $periodo = PeriodoNomina::create([
            'fecha_inicio' => '2026-10-19',
            'fecha_fin' => '2026-10-25',
            'estatus' => 'generada',
        ]);

        $respuesta = $this->actingAs($usuario)->get(route('nomina-semanal.pdf', $periodo));

        $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($usuario)
            ->get(route('nomina-semanal.pdf.preview', $periodo))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');
    }

    public function test_descarga_el_recibo_de_nomina_fuera_de_livewire(): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);
        $empleado = Empleado::create([
            'numero_empleado' => 'PDF-001',
            'nombre' => 'Empleado',
            'apellido_paterno' => 'Recibo',
            'sucursal_id' => $sucursal->id,
            'fecha_ingreso' => '2026-10-01',
            'estatus' => 'activo',
        ]);
        $periodo = PeriodoNomina::create([
            'fecha_inicio' => '2026-10-19',
            'fecha_fin' => '2026-10-25',
            'estatus' => 'generada',
        ]);
        $nomina = NominaSemanal::create([
            'periodo_nomina_id' => $periodo->id,
            'empleado_id' => $empleado->id,
        ]);

        $respuesta = $this->actingAs($usuario)->get(route('nomina-semanal.recibo', $nomina));

        $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($usuario)
            ->get(route('nomina-semanal.recibo.preview', $nomina))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');
    }
}
