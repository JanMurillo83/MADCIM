<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table): void {
            $table->decimal('efectivo_teorico', 12, 2)->default(0)->after('total_egresos_cash');
            $table->decimal('efectivo_contado', 12, 2)->default(0)->after('efectivo_teorico');
            $table->json('denominaciones_efectivo')->nullable()->after('efectivo_contado');
        });
    }

    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table): void {
            $table->dropColumn(['efectivo_teorico', 'efectivo_contado', 'denominaciones_efectivo']);
        });
    }
};
