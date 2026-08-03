<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table): void {
            $table->decimal('importe_recibido', 15, 2)->nullable()->after('importe');
            $table->decimal('cambio', 15, 2)->default(0)->after('importe_recibido');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table): void {
            $table->dropColumn(['importe_recibido', 'cambio']);
        });
    }
};
