<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('recepcion_compra_partidas', 'existencia_sucursal_antes')) {
            Schema::table('recepcion_compra_partidas', function (Blueprint $table): void {
                $table->decimal('existencia_sucursal_antes', 18, 8)->nullable()->after('existencia_antes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('recepcion_compra_partidas', 'existencia_sucursal_antes')) {
            Schema::table('recepcion_compra_partidas', function (Blueprint $table): void {
                $table->dropColumn('existencia_sucursal_antes');
            });
        }
    }
};
