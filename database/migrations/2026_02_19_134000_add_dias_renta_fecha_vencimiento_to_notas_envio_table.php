<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_envio', function (Blueprint $table) {
            $table->integer('dias_renta')->nullable()->after('fecha_emision');
            $table->date('fecha_vencimiento')->nullable()->after('dias_renta');
        });
    }

    public function down(): void
    {
        Schema::table('notas_envio', function (Blueprint $table) {
            $table->dropColumn(['dias_renta', 'fecha_vencimiento']);
        });
    }
};
