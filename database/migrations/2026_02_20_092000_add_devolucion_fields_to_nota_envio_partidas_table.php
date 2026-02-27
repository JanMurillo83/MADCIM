<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nota_envio_partidas', function (Blueprint $table) {
            $table->decimal('cantidad_devuelta', 12, 2)->default(0)->after('cantidad');
            $table->string('estado')->default('Activo')->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('nota_envio_partidas', function (Blueprint $table) {
            $table->dropColumn(['cantidad_devuelta', 'estado']);
        });
    }
};
