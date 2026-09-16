<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('notas_venta_renta', 'metros_m2')) {
            Schema::table('notas_venta_renta', function (Blueprint $table) {
                $table->decimal('metros_m2', 12, 2)->nullable()->after('tipo_nota_renta');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notas_venta_renta', 'metros_m2')) {
            Schema::table('notas_venta_renta', function (Blueprint $table) {
                $table->dropColumn('metros_m2');
            });
        }
    }
};
