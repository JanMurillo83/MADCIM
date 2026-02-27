<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_envio', function (Blueprint $table) {
            // Hacer nota_venta_renta_id nullable para permitir envíos de venta-venta
            $table->foreignId('nota_venta_venta_id')->nullable()->after('nota_venta_renta_id')->constrained('notas_venta_venta')->nullOnDelete();
        });

        // Hacer nota_venta_renta_id nullable
        Schema::table('notas_envio', function (Blueprint $table) {
            $table->unsignedBigInteger('nota_venta_renta_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notas_envio', function (Blueprint $table) {
            $table->dropForeign(['nota_venta_venta_id']);
            $table->dropColumn('nota_venta_venta_id');
        });
    }
};
