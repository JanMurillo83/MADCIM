<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->string('pac_contract', 100)->nullable();
            $table->string('pac_user', 100)->nullable();
            $table->text('pac_password')->nullable();
            $table->boolean('pac_modo_prueba')->default(true);
            $table->text('pac_timbrado_endpoint')->nullable();
            $table->text('pac_cancelacion_endpoint')->nullable();
        });

        DB::table('configuracion')->whereNull('pac_timbrado_endpoint')->update([
            'pac_timbrado_endpoint' => 'https://timbrado.pade.mx/servicio/rest/timbrado40/timbrarConCSD',
        ]);
        DB::table('configuracion')->whereNull('pac_cancelacion_endpoint')->update([
            'pac_cancelacion_endpoint' => 'https://timbrado.pade.mx/servicio/rest/integracion/cancelarCfdiConOpciones',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'pac_contract',
                'pac_user',
                'pac_password',
                'pac_modo_prueba',
                'pac_timbrado_endpoint',
                'pac_cancelacion_endpoint',
            ]);
        });
    }
};
