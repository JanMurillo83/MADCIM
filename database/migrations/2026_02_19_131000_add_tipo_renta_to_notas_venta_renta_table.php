<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('notas_venta_renta', 'tipo_renta')) {
            Schema::table('notas_venta_renta', function (Blueprint $table) {
                $table->string('tipo_renta', 20)->nullable()->default('dia')->after('dias_renta');
            });
        }
    }

    public function down(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            $table->dropColumn('tipo_renta');
        });
    }
};
