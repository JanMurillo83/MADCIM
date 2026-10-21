<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\Horario;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class EmpleadosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $sucursal = Sucursal::query()->firstOrCreate(
            ['codigo' => 'HUA'],
            [
                'nombre' => 'MADCIM HUATULCO',
                'activa' => true,
            ],
        );

        $horario = Horario::query()->firstOrCreate(
            [
                'sucursal_id' => $sucursal->id,
                'nombre' => 'Horario operativo demo',
            ],
            [
                'hora_entrada' => '08:00',
                'hora_salida_comida' => '14:00',
                'hora_regreso_comida' => '15:00',
                'hora_salida' => '18:00',
                'tolerancia_entrada' => 10,
                'tolerancia_regreso_comida' => 10,
                'duracion_comida' => 60,
                'dia_descanso' => 0,
                'activo' => true,
            ],
        );

        $empleados = [
            ['100001', 'Adriana Lizeth', 'Gonzalez', 'Lopez', 'Ventas', 'Vendedora', 400, 2800],
            ['100002', 'Francisco Jesus', 'Gutierrez', 'Sanchez', 'Carpinteria', 'Carpintero', 685.71, 4800],
            ['100003', 'Geovanny Luna', 'Martinez', null, 'Carpinteria', 'Ayudante de carpinteria', 428.57, 3000],
            ['100004', 'Gladys Bautista', 'Reyes', null, 'Carpinteria', 'Encargada de lijado', 528.57, 3700],
            ['100005', 'Ignacio Sanchez', 'Sanchez', null, 'Carpinteria', 'Ayudante de carpinteria', 400, 2800],
            ['100006', 'Jesus Antonio', 'Pena', 'Merino', 'Herrería', 'Herrero', 600, 4200],
            ['100007', 'Jose Eduardo', 'Velazquez', 'Santos', 'Carpinteria', 'Ayudante de carpinteria', 571.43, 4000],
            ['100008', 'Jorge Alejandro', 'Meza', 'Santiago', 'Carpinteria', 'Ayudante de carpinteria', 557.14, 3900],
            ['100009', 'Leydi Josefina', 'Garcia', 'Ramirez', 'Inventarios', 'Auxiliar de inventarios', 571.43, 4000],
            ['100010', 'Manuel Antonio', 'Mendoza', 'Martinez', 'Ventas', 'Vendedor de mostrador', 500, 3500],
        ];

        foreach ($empleados as [$numero, $nombre, $apellidoPaterno, $apellidoMaterno, $departamento, $puesto, $sueldoDiario, $sueldoSemanal]) {
            Empleado::query()->updateOrCreate(
                ['numero_empleado' => $numero],
                [
                    'nombre' => $nombre,
                    'apellido_paterno' => $apellidoPaterno,
                    'apellido_materno' => $apellidoMaterno,
                    'sucursal_id' => $sucursal->id,
                    'horario_id' => $horario->id,
                    'departamento' => $departamento,
                    'puesto' => $puesto,
                    'fecha_ingreso' => now()->subMonths(6)->toDateString(),
                    'tipo_contrato' => 'indeterminado',
                    'sueldo_diario' => $sueldoDiario,
                    'sueldo_semanal' => $sueldoSemanal,
                    'estatus' => 'activo',
                ],
            );
        }
    }
}
