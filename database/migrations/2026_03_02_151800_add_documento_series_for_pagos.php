<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $series = [
            ['serie' => 'M', 'descripcion' => 'MADERERIA'],
            ['serie' => 'C', 'descripcion' => 'CARPINTERIA'],
            ['serie' => 'F', 'descripcion' => 'FERRETERIA'],
        ];

        foreach ($series as $serie) {
            $exists = DB::table('documento_series')
                ->where('documento_tipo', 'pagos')
                ->where('serie', $serie['serie'])
                ->exists();

            if (!$exists) {
                DB::table('documento_series')->insert([
                    'documento_tipo' => 'pagos',
                    'serie' => $serie['serie'],
                    'descripcion' => $serie['descripcion'],
                    'ultimo_folio' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('documento_series')->where('documento_tipo', 'pagos')->delete();
    }
};
