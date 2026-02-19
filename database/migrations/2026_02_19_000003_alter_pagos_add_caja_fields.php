<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('pagos', 'caja_id')) {
                $table->foreignId('caja_id')->nullable()->after('user_id')->constrained('cajas')->nullOnDelete();
            }
            if (!Schema::hasColumn('pagos', 'metodo_pago')) {
                $table->string('metodo_pago')->nullable()->after('forma_pago');
            }
            if (!Schema::hasColumn('pagos', 'referencia')) {
                $table->string('referencia')->nullable()->after('importe');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (Schema::hasColumn('pagos', 'caja_id')) {
                $table->dropConstrainedForeignId('caja_id');
            }
            if (Schema::hasColumn('pagos', 'metodo_pago')) {
                $table->dropColumn('metodo_pago');
            }
            if (Schema::hasColumn('pagos', 'referencia')) {
                $table->dropColumn('referencia');
            }
        });
    }
};
