<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominas_semanales', function (Blueprint $table): void {
            $table->unsignedSmallInteger('dias_programados')->default(0)->after('sueldo_base');
            $table->unsignedSmallInteger('dias_trabajados')->default(0)->after('dias_programados');
            $table->unsignedSmallInteger('dias_descanso_laborados')->default(0)->after('dias_trabajados');
            $table->unsignedSmallInteger('dias_festivos')->default(0)->after('dias_descanso_laborados');
            $table->decimal('importe_descanso_laborado', 12, 2)->default(0)->after('dias_festivos');
            $table->decimal('importe_dia_festivo', 12, 2)->default(0)->after('importe_descanso_laborado');
            $table->decimal('aguinaldo', 12, 2)->default(0)->after('bonos');
            $table->decimal('apoyos', 12, 2)->default(0)->after('aguinaldo');
            $table->decimal('anticipos', 12, 2)->default(0)->after('apoyos');
            $table->decimal('prestamos', 12, 2)->default(0)->after('anticipos');
        });

        Schema::table('conceptos_nomina', function (Blueprint $table): void {
            $table->decimal('cantidad', 8, 2)->default(1)->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('nominas_semanales', function (Blueprint $table): void {
            $table->dropColumn([
                'dias_programados',
                'dias_trabajados',
                'dias_descanso_laborados',
                'dias_festivos',
                'importe_descanso_laborado',
                'importe_dia_festivo',
                'aguinaldo',
                'apoyos',
                'anticipos',
                'prestamos',
            ]);
        });

        Schema::table('conceptos_nomina', function (Blueprint $table): void {
            $table->dropColumn('cantidad');
        });
    }
};
