<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table): void {
            $table->json('reporte_cierre')->nullable();
        });

        Schema::table('caja_cortes', function (Blueprint $table): void {
            $table->json('reporte_cierre')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('caja_cortes', function (Blueprint $table): void {
            $table->dropColumn('reporte_cierre');
        });

        Schema::table('cajas', function (Blueprint $table): void {
            $table->dropColumn('reporte_cierre');
        });
    }
};
