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
            $table->foreignId('direccion_entrega_id')->nullable()->after('cliente_id')->constrained('cliente_direcciones_entrega')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            $table->dropForeign(['direccion_entrega_id']);
            $table->dropColumn('direccion_entrega_id');
        });
    }
};
