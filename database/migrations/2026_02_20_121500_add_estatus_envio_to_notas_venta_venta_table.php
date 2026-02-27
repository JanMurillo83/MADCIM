<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_venta_venta', function (Blueprint $table) {
            $table->enum('estatus_envio', ['Pendiente de Envío', 'Enviada'])->default('Pendiente de Envío')->after('estatus');
        });
    }

    public function down(): void
    {
        Schema::table('notas_venta_venta', function (Blueprint $table) {
            $table->dropColumn('estatus_envio');
        });
    }
};
