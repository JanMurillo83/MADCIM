<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recepcion_compra_partidas', function (Blueprint $table) {
            $table->decimal('existencia_antes', 18, 8)->nullable()->after('total');
            $table->decimal('costo_promedio_antes', 18, 8)->nullable()->after('existencia_antes');
            $table->decimal('ultimo_costo_antes', 18, 8)->nullable()->after('costo_promedio_antes');
        });
    }

    public function down(): void
    {
        Schema::table('recepcion_compra_partidas', function (Blueprint $table) {
            $table->dropColumn(['existencia_antes', 'costo_promedio_antes', 'ultimo_costo_antes']);
        });
    }
};
