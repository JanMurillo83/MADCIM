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
        Schema::table('registro_rentas', function (Blueprint $table) {
            $table->foreignId('cliente_id')->after('nota_venta_renta_id')->constrained('clientes')->onDelete('restrict');
            $table->string('cliente_nombre')->after('cliente_id');
            $table->string('cliente_contacto')->after('cliente_nombre')->nullable();
            $table->string('cliente_telefono')->after('cliente_contacto')->nullable();
            $table->text('cliente_direccion')->after('cliente_telefono')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_rentas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn([
                'cliente_id',
                'cliente_nombre',
                'cliente_contacto',
                'cliente_telefono',
                'cliente_direccion'
            ]);
        });
    }
};
