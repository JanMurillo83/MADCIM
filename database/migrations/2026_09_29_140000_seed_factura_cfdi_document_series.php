<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['M', 'MADERERIA'],
            ['C', 'CARPINTERIA'],
            ['F', 'FERRETERIA'],
        ] as [$serie, $descripcion]) {
            DB::table('documento_series')->insertOrIgnore([
                'documento_tipo' => 'facturas_cfdi',
                'serie' => $serie,
                'descripcion' => $descripcion,
                'ultimo_folio' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Se conservan series y folios una vez creados.
    }
};
