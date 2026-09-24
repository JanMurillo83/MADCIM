<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['notas_venta_renta', 'notas_venta_venta'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'cobro_credito_confirmado_en')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->timestamp('cobro_credito_confirmado_en')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['notas_venta_renta', 'notas_venta_venta'] as $tableName) {
            if (Schema::hasColumn($tableName, 'cobro_credito_confirmado_en')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('cobro_credito_confirmado_en');
                });
            }
        }
    }
};
