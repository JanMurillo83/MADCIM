<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('nominas_semanales', function (Blueprint $table): void {
            $table->unsignedSmallInteger('faltas')->default(0)->after('sueldo_base');
            $table->unsignedInteger('minutos_retardo')->default(0)->after('faltas');
            $table->decimal('descuento_faltas', 12, 2)->default(0)->after('minutos_retardo');
            $table->decimal('descuento_retardos', 12, 2)->default(0)->after('descuento_faltas');
            $table->decimal('otros_descuentos', 12, 2)->default(0)->after('descuento_retardos');
        });
    }
    public function down(): void {
        Schema::table('nominas_semanales', function (Blueprint $table): void {
            $table->dropColumn(['faltas', 'minutos_retardo', 'descuento_faltas', 'descuento_retardos', 'otros_descuentos']);
        });
    }
};
