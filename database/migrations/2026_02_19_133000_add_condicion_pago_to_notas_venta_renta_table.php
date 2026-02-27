<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('notas_venta_renta', 'condicion_pago')) {
            Schema::table('notas_venta_renta', function (Blueprint $table) {
                $table->string('condicion_pago', 20)->default('contado')->after('tipo_renta');
            });
        }
    }

    public function down(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            $table->dropColumn('condicion_pago');
        });
    }
};
