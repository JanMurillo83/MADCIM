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
        Schema::table('facturas_cfdi', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('id')->constrained('clientes')->onDelete('cascade');
            $table->decimal('saldo_pendiente', 18, 8)->default(0)->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas_cfdi', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn(['cliente_id', 'saldo_pendiente']);
        });
    }
};
