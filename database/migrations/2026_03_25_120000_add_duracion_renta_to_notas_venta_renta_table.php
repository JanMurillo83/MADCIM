<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            if (!Schema::hasColumn('notas_venta_renta', 'duracion_renta')) {
                $table->unsignedInteger('duracion_renta')->nullable()->after('dias_renta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            if (Schema::hasColumn('notas_venta_renta', 'duracion_renta')) {
                $table->dropColumn('duracion_renta');
            }
        });
    }
};
