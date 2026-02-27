<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class SucursalesSeeder extends Seeder
{
    public function run(): void
    {
        $sucursales = [
            [
                'nombre' => 'MADCIM HUATULCO',
                'codigo' => 'HUA',
                'direccion' => null,
                'telefono' => null,
                'activa' => true,
            ],
            [
                'nombre' => 'MADCIM POCHUTLA',
                'codigo' => 'POCH',
                'direccion' => null,
                'telefono' => null,
                'activa' => true,
            ],
        ];

        foreach ($sucursales as $sucursal) {
            Sucursal::firstOrCreate(
                ['codigo' => $sucursal['codigo']],
                $sucursal
            );
        }
    }
}
