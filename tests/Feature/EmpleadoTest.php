<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\Horario;
use App\Models\Sucursal;
use App\Services\RegistrarChecadaService;
use App\Enums\MetodoChecada;
use App\Enums\TipoChecada;
use LogicException;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpleadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_empleado_guarda_su_relacion_de_sucursal_y_nombre_completo(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);

        $empleado = Empleado::create([
            'numero_empleado' => 'RH-001',
            'nombre' => 'Ana',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Santos',
            'sucursal_id' => $sucursal->id,
            'fecha_ingreso' => '2026-10-20',
            'estatus' => 'activo',
        ]);

        $this->assertSame('Ana López Santos', $empleado->nombre_completo);
        $this->assertSame('MADCIM HUATULCO', $empleado->sucursal->nombre);
    }

    public function test_roles_de_rh_pueden_acceder_al_catalogo_de_empleados(): void
    {
        $resource = \App\Filament\Resources\Empleados\EmpleadoResource::class;

        $this->assertTrue(RolePermissions::canAccessResource($resource, RolePermissions::ROLE_RH));
        $this->assertTrue(RolePermissions::canAccessResource($resource, RolePermissions::ROLE_NOMINA));
        $this->assertTrue(RolePermissions::canAccessResource($resource, RolePermissions::ROLE_GERENCIA));
        $this->assertFalse(RolePermissions::canAccessResource($resource, RolePermissions::ROLE_EMPLEADO));
    }

    public function test_empleado_puede_tener_un_horario_configurable(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);

        $horario = Horario::create([
            'nombre' => 'Horario estándar',
            'sucursal_id' => $sucursal->id,
            'hora_entrada' => '08:00',
            'hora_salida_comida' => '14:00',
            'hora_regreso_comida' => '15:00',
            'hora_salida' => '18:00',
            'tolerancia_entrada' => 10,
            'tolerancia_regreso_comida' => 10,
            'duracion_comida' => 60,
            'dia_descanso' => 0,
            'activo' => true,
        ]);

        $empleado = Empleado::create([
            'numero_empleado' => 'RH-002',
            'nombre' => 'Luis',
            'apellido_paterno' => 'García',
            'sucursal_id' => $sucursal->id,
            'horario_id' => $horario->id,
            'fecha_ingreso' => '2026-10-20',
            'estatus' => 'activo',
        ]);

        $this->assertSame('Horario estándar', $empleado->fresh()->horario->nombre);
        $this->assertSame(10, $horario->tolerancia_entrada);
        $this->assertSame(60, $horario->duracion_comida);
    }

    public function test_registra_checadas_con_servicio_y_no_permite_modificar_el_original(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);
        $empleado = Empleado::create([
            'numero_empleado' => 'RH-003',
            'nombre' => 'Marta',
            'apellido_paterno' => 'Pérez',
            'sucursal_id' => $sucursal->id,
            'fecha_ingreso' => '2026-10-20',
            'estatus' => 'activo',
        ]);

        $checada = app(RegistrarChecadaService::class)->registrar([
            'empleado_id' => $empleado->id,
            'sucursal_id' => $sucursal->id,
            'tipo' => TipoChecada::Entrada,
            'metodo' => MetodoChecada::Huella,
            'dispositivo' => 'Reloj HUA-01',
            'registrada_en' => '2026-10-20 08:03:00',
        ]);

        $this->assertSame(TipoChecada::Entrada, $checada->tipo);
        $this->assertSame(MetodoChecada::Huella, $checada->metodo);
        $this->assertSame('Reloj HUA-01', $checada->dispositivo);

        $this->expectException(LogicException::class);
        $checada->update(['dispositivo' => 'alterado']);
    }
}
