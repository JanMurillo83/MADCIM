<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            // Verificar si la columna no existe antes de agregarla
            if (!Schema::hasColumn('notas_venta_renta', 'dias_renta')) {
                $table->integer('dias_renta')->nullable()->after('fecha_emision');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            if (Schema::hasColumn('notas_venta_renta', 'dias_renta')) {
                $table->dropColumn('dias_renta');
            }
        });
    }
};
