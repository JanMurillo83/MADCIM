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
        Schema::create('configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('rfc');
            $table->string('regimen');
            $table->string('codigo');
            $table->string('calle')->nullable();
            $table->string('exterior')->nullable();
            $table->string('interior')->nullable();
            $table->string('colonia')->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->nullable();
            $table->string('pais')->nullable();
            $table->string('sello_cer')->nullable();
            $table->string('sello_key')->nullable();
            $table->string('sello_pass')->nullable();
            $table->string('api_key')->nullable();
            $table->longText('logo')->nullable();
            $table->decimal('por_tab_com',18,8)->default(0);
            $table->decimal('por_tab_ped',18,8)->default(0);
            $table->decimal('imp_tabla_met',18,8)->default(0);
            $table->decimal('imp_tabla_dep',18,8)->default(0);
            $table->decimal('imp_triqui_met',18,8)->default(0);
            $table->decimal('imp_triqui_dep',18,8)->default(0);
            $table->decimal('imp_tridie_met',18,8)->default(0);
            $table->decimal('imp_tridie_dep',18,8)->default(0);
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('configuracion')->insert([
            'razon_social'=>'MADERERIA MADCIM',
            'rfc'=>'XAXX010101000',
            'regimen'=>'616',
            'codigo'=>'12345',
            'por_tab_com'=>80,
            'por_tab_ped'=>20,
            'imp_tabla_met'=>120,
            'imp_tabla_dep'=>60,
            'imp_triqui_met'=>250,
            'imp_triqui_dep'=>125,
            'imp_tridie_met'=>280,
            'imp_tridie_dep'=>140
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracions');
    }
};
