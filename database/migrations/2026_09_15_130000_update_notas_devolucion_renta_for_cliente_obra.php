<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_devolucion_renta', function (Blueprint $table) {
            if (!Schema::hasColumn('notas_devolucion_renta', 'direccion_entrega_id')) {
                $table->foreignId('direccion_entrega_id')
                    ->nullable()
                    ->after('cliente_id')
                    ->constrained('cliente_direcciones_entrega')
                    ->nullOnDelete();
            }
        });

        Schema::table('nota_devolucion_renta_partidas', function (Blueprint $table) {
            if (!Schema::hasColumn('nota_devolucion_renta_partidas', 'cantidad_enviada')) {
                $table->decimal('cantidad_enviada', 12, 2)->default(0)->after('descripcion');
            }
            if (!Schema::hasColumn('nota_devolucion_renta_partidas', 'cantidad_devuelta')) {
                $table->decimal('cantidad_devuelta', 12, 2)->default(0)->after('cantidad_enviada');
            }
            if (!Schema::hasColumn('nota_devolucion_renta_partidas', 'cantidad_a_devolver')) {
                $table->decimal('cantidad_a_devolver', 12, 2)->default(0)->after('cantidad_devuelta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nota_devolucion_renta_partidas', function (Blueprint $table) {
            foreach (['cantidad_a_devolver', 'cantidad_devuelta', 'cantidad_enviada'] as $column) {
                if (Schema::hasColumn('nota_devolucion_renta_partidas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('notas_devolucion_renta', function (Blueprint $table) {
            if (Schema::hasColumn('notas_devolucion_renta', 'direccion_entrega_id')) {
                $table->dropConstrainedForeignId('direccion_entrega_id');
            }
        });
    }
};
