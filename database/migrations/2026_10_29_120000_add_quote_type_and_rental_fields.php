<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('tipo_cotizacion', 10)->default('venta');
            $table->decimal('deposito', 18, 8)->default(0);
        });

        Schema::table('cotizacion_partidas', function (Blueprint $table) {
            $table->string('tipo_nota_renta', 20)->nullable();
            $table->string('tipo_renta', 20)->nullable();
            $table->unsignedInteger('duracion_renta')->nullable();
            $table->unsignedInteger('dias_renta')->nullable();
            $table->decimal('metros_m2', 18, 8)->nullable();
            $table->decimal('deposito', 18, 8)->default(0);
        });

        // Las cotizaciones previas generadas por el cotizador de renta tienen los conceptos identificables.
        if (Schema::hasTable('productos')) {
            \Illuminate\Support\Facades\DB::table('cotizaciones')
                ->whereExists(function ($query) {
                    $query->selectRaw('1')->from('cotizacion_partidas')
                        ->join('productos', 'productos.id', '=', 'cotizacion_partidas.item')
                        ->whereColumn('cotizacion_partidas.cotizacion_id', 'cotizaciones.id')
                        ->whereIn('productos.clave', ['SRENTA-M2', 'DEPOGARANTIA']);
                })->update(['tipo_cotizacion' => 'renta']);
        }
    }

    public function down(): void
    {
        Schema::table('cotizacion_partidas', function (Blueprint $table) {
            $table->dropColumn(['tipo_nota_renta', 'tipo_renta', 'duracion_renta', 'dias_renta', 'metros_m2', 'deposito']);
        });
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['tipo_cotizacion', 'deposito']);
        });
    }
};
