<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('nota_envio_partidas', 'dias_renta')) {
            Schema::table('nota_envio_partidas', function (Blueprint $table): void {
                $table->unsignedInteger('dias_renta')->nullable()->after('cantidad');
            });
        }

        if (!Schema::hasColumn('nota_envio_partidas', 'fecha_vencimiento')) {
            Schema::table('nota_envio_partidas', function (Blueprint $table): void {
                $table->date('fecha_vencimiento')->nullable()->after('dias_renta');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('nota_envio_partidas', 'fecha_vencimiento')) {
            Schema::table('nota_envio_partidas', function (Blueprint $table): void {
                $table->dropColumn('fecha_vencimiento');
            });
        }

        if (Schema::hasColumn('nota_envio_partidas', 'dias_renta')) {
            Schema::table('nota_envio_partidas', function (Blueprint $table): void {
                $table->dropColumn('dias_renta');
            });
        }
    }
};
