<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('caja_movimientos', 'aplicado_a_caja')) {
            Schema::table('caja_movimientos', function (Blueprint $table): void {
                $table->boolean('aplicado_a_caja')->default(false)->after('movimentable_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('caja_movimientos', 'aplicado_a_caja')) {
            Schema::table('caja_movimientos', function (Blueprint $table): void {
                $table->dropColumn('aplicado_a_caja');
            });
        }
    }
};
