<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_envio', function (Blueprint $table) {
            $table->enum('estado_renta', ['Vigente', 'Vencido', 'Devuelta'])->default('Vigente')->after('estatus');
        });
    }

    public function down(): void
    {
        Schema::table('notas_envio', function (Blueprint $table) {
            $table->dropColumn('estado_renta');
        });
    }
};
