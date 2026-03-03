<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['factura_cfdi_partidas', 'devolucion_renta_partidas', 'devolucion_venta_partidas'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'clave_prod_serv')) {
                    $table->string('clave_prod_serv', 8)->nullable()->after('item');
                }
                if (!Schema::hasColumn($tableName, 'no_identificacion')) {
                    $table->string('no_identificacion')->nullable()->after('clave_prod_serv');
                }
                if (!Schema::hasColumn($tableName, 'clave_unidad')) {
                    $table->string('clave_unidad', 3)->nullable()->after('cantidad');
                }
                if (!Schema::hasColumn($tableName, 'unidad')) {
                    $table->string('unidad')->nullable()->after('clave_unidad');
                }
                if (!Schema::hasColumn($tableName, 'objeto_imp')) {
                    $table->string('objeto_imp', 2)->nullable()->after('descripcion');
                }
                if (!Schema::hasColumn($tableName, 'descuento')) {
                    $table->decimal('descuento', 18, 8)->default(0)->after('subtotal');
                }
            });
        }

        foreach (['factura_cfdi_partidas', 'devolucion_renta_partidas', 'devolucion_venta_partidas'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('clave_prod_serv')->references('clave')->on('sat_clave_prod_serv')->nullOnDelete();
                $table->foreign('clave_unidad')->references('clave')->on('sat_clave_unidad')->nullOnDelete();
                $table->foreign('objeto_imp')->references('clave')->on('sat_objeto_imp')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['factura_cfdi_partidas', 'devolucion_renta_partidas', 'devolucion_venta_partidas'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['clave_prod_serv']);
                $table->dropForeign(['clave_unidad']);
                $table->dropForeign(['objeto_imp']);
            });
        }

        foreach (['factura_cfdi_partidas', 'devolucion_renta_partidas', 'devolucion_venta_partidas'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'clave_prod_serv',
                    'no_identificacion',
                    'clave_unidad',
                    'unidad',
                    'objeto_imp',
                    'descuento',
                ]);
            });
        }
    }
};
