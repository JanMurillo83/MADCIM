<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_devolucion_renta', function (Blueprint $table) {
            $table->foreignId('nota_envio_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notas_devolucion_renta', function (Blueprint $table) {
            $table->foreignId('nota_envio_id')->nullable(false)->change();
        });
    }
};
