<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_envio', function (Blueprint $table): void {
            $table->date('inicio_vigencia')->nullable()->after('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::table('notas_envio', function (Blueprint $table): void {
            $table->dropColumn('inicio_vigencia');
        });
    }
};
