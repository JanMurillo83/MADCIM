<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_devolucion_renta', function (Blueprint $table) {
            if (!Schema::hasColumn('notas_devolucion_renta', 'nota_venta_renta_id')) {
                $table->foreignId('nota_venta_renta_id')
                    ->nullable()
                    ->after('nota_envio_id')
                    ->constrained('notas_venta_renta')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notas_devolucion_renta', function (Blueprint $table) {
            if (Schema::hasColumn('notas_devolucion_renta', 'nota_venta_renta_id')) {
                $table->dropConstrainedForeignId('nota_venta_renta_id');
            }
        });
    }
};
