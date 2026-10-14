<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('movimientos_inventario', 'sucursal_id')) {
            Schema::table('movimientos_inventario', function (Blueprint $table): void {
                $table->foreignId('sucursal_id')->nullable()->after('producto_id')->constrained('sucursales')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('movimientos_inventario', 'sucursal_id')) {
            Schema::table('movimientos_inventario', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('sucursal_id');
            });
        }
    }
};
