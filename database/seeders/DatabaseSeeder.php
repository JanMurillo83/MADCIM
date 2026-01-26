<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@madcim.com',
            'password'=> Hash::make('admin')
        ]);
        DB::table('grupos')->insert(['nombre'=>'ANDAMIO']);
        DB::table('grupos')->insert(['nombre'=>'BAILARINA']);
        DB::table('grupos')->insert(['nombre'=>'BARROTE']);
        DB::table('grupos')->insert(['nombre'=>'COMPRESOR']);
        DB::table('grupos')->insert(['nombre'=>'CORTADORA']);
        DB::table('grupos')->insert(['nombre'=>'DEPOSITO']);
        DB::table('grupos')->insert(['nombre'=>'DESBROZADORA']);
        DB::table('grupos')->insert(['nombre'=>'DESBROZADORA-M01']);
        DB::table('grupos')->insert(['nombre'=>'DUELA']);
        DB::table('grupos')->insert(['nombre'=>'ESCALERA']);
        DB::table('grupos')->insert(['nombre'=>'ESMERIL']);
        DB::table('grupos')->insert(['nombre'=>'GENERADOR']);
        DB::table('grupos')->insert(['nombre'=>'HIDROLAVADORA']);
        DB::table('grupos')->insert(['nombre'=>'LLANTAS']);
        DB::table('grupos')->insert(['nombre'=>'MANGUERA']);
        DB::table('grupos')->insert(['nombre'=>'MONTACARGAS']);
        DB::table('grupos')->insert(['nombre'=>'MOTOBOMBA']);
        DB::table('grupos')->insert(['nombre'=>'MOTOSIERRA']);
        DB::table('grupos')->insert(['nombre'=>'PIES']);
        DB::table('grupos')->insert(['nombre'=>'POLIN']);
        DB::table('grupos')->insert(['nombre'=>'REFACCIONES']);
        DB::table('grupos')->insert(['nombre'=>'RENTA']);
        DB::table('grupos')->insert(['nombre'=>'RETEN']);
        DB::table('grupos')->insert(['nombre'=>'REVOLVEDORA']);
        DB::table('grupos')->insert(['nombre'=>'ROTOMARTILLO']);
        DB::table('grupos')->insert(['nombre'=>'SIERRA']);
        DB::table('grupos')->insert(['nombre'=>'TABLA']);
        DB::table('grupos')->insert(['nombre'=>'TABLON']);
        DB::table('grupos')->insert(['nombre'=>'TRIPLAY']);
        DB::table('grupos')->insert(['nombre'=>'VIBRADOR']);

        DB::table('lineas')->insert(['nombre'=>'EQUIPO']);
        DB::table('lineas')->insert(['nombre'=>'MADERA']);
        DB::table('lineas')->insert(['nombre'=>'DEPOSITOS EN GARANTIA']);

        $csvFile = fopen(public_path('csvdata/productos.csv'), "r");
        $firstline = true;
        while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
            if (!$firstline) {
                DB::table('productos')->insert([
                    'clave'=> $data[0],
                    'descripcion'=> $data[1],
                    'm2_cubre'=> $data[2],
                    'precio_venta'=> $data[3],
                    'precio_renta_mes'=> $data[4],
                    'precio_renta_dia'=> $data[5],
                    'precio_renta_semana'=> $data[6],
                    'existencia'=> $data[7],
                    'grupo'=> $data[8],
                    'linea'=> $data[9],
                    'largo'=> $data[10],
                    'ancho'=> $data[11],
                ]);
            }
            $firstline = false;
        }
        fclose($csvFile);
    }
}
