<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            if (!Schema::hasColumn('notas_venta_renta', 'tipo_nota_renta')) {
                $table->string('tipo_nota_renta', 30)->default('equipo')->after('estatus')->index();
            }
            if (!Schema::hasColumn('notas_venta_renta', 'dias_solicitados')) {
                $table->integer('dias_solicitados')->nullable()->after('dias_renta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            if (Schema::hasColumn('notas_venta_renta', 'dias_solicitados')) {
                $table->dropColumn('dias_solicitados');
            }
            if (Schema::hasColumn('notas_venta_renta', 'tipo_nota_renta')) {
                $table->dropColumn('tipo_nota_renta');
            }
        });
    }
};
