<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'clave_prod_serv')) {
                $table->string('clave_prod_serv', 8)->nullable()->after('clave');
            }
            if (!Schema::hasColumn('productos', 'clave_unidad')) {
                $table->string('clave_unidad', 3)->nullable()->after('clave_prod_serv');
            }
            if (!Schema::hasColumn('productos', 'unidad_sat')) {
                $table->string('unidad_sat')->nullable()->after('clave_unidad');
            }
            if (!Schema::hasColumn('productos', 'objeto_imp')) {
                $table->string('objeto_imp', 2)->nullable()->after('unidad_sat');
            }
            if (!Schema::hasColumn('productos', 'impuesto')) {
                $table->string('impuesto', 3)->nullable()->after('objeto_imp');
            }
            if (!Schema::hasColumn('productos', 'tipo_factor')) {
                $table->string('tipo_factor', 10)->nullable()->after('impuesto');
            }
            if (!Schema::hasColumn('productos', 'tasa_o_cuota')) {
                $table->decimal('tasa_o_cuota', 18, 6)->nullable()->after('tipo_factor');
            }
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->foreign('clave_prod_serv')->references('clave')->on('sat_clave_prod_serv')->nullOnDelete();
            $table->foreign('clave_unidad')->references('clave')->on('sat_clave_unidad')->nullOnDelete();
            $table->foreign('objeto_imp')->references('clave')->on('sat_objeto_imp')->nullOnDelete();
            $table->foreign('impuesto')->references('clave')->on('sat_impuesto')->nullOnDelete();
            $table->foreign('tipo_factor')->references('clave')->on('sat_tipo_factor')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['clave_prod_serv']);
            $table->dropForeign(['clave_unidad']);
            $table->dropForeign(['objeto_imp']);
            $table->dropForeign(['impuesto']);
            $table->dropForeign(['tipo_factor']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'clave_prod_serv',
                'clave_unidad',
                'unidad_sat',
                'objeto_imp',
                'impuesto',
                'tipo_factor',
                'tasa_o_cuota',
            ]);
        });
    }
};
