<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente_direcciones_entrega', function (Blueprint $table) {
            if (!Schema::hasColumn('cliente_direcciones_entrega', 'referencia')) {
                $table->string('referencia')->nullable()->after('pais');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cliente_direcciones_entrega', function (Blueprint $table) {
            if (Schema::hasColumn('cliente_direcciones_entrega', 'referencia')) {
                $table->dropColumn('referencia');
            }
        });
    }
};
