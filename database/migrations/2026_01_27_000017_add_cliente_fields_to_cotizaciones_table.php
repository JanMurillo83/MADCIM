<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->nullOnDelete();
            $table->text('direccion_entrega')->nullable();
            $table->text('observaciones')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn(['cliente_id', 'direccion_entrega', 'observaciones']);
        });
    }
};
