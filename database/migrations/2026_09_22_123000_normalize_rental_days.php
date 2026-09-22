<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notas_venta_renta')
            ->where('dias_renta', '<', 1)
            ->update(['dias_renta' => 1]);

        DB::table('nota_venta_renta_partidas')
            ->whereNotNull('dias_renta')
            ->where('dias_renta', '<', 1)
            ->update(['dias_renta' => 1]);
    }

    public function down(): void
    {
        // La normalización no se revierte: los días negativos nunca son válidos.
    }
};
