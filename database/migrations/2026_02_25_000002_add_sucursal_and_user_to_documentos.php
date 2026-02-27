<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            if (!Schema::hasColumn('notas_venta_renta', 'sucursal_id')) {
                $table->foreignId('sucursal_id')->nullable()->after('direccion_entrega_id')->constrained('sucursales')->nullOnDelete();
            }
            if (!Schema::hasColumn('notas_venta_renta', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('sucursal_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('notas_venta_venta', function (Blueprint $table) {
            if (!Schema::hasColumn('notas_venta_venta', 'sucursal_id')) {
                $table->foreignId('sucursal_id')->nullable()->after('cliente_id')->constrained('sucursales')->nullOnDelete();
            }
            if (!Schema::hasColumn('notas_venta_venta', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('sucursal_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('facturas_cfdi', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas_cfdi', 'sucursal_id')) {
                $table->foreignId('sucursal_id')->nullable()->after('cliente_id')->constrained('sucursales')->nullOnDelete();
            }
            if (!Schema::hasColumn('facturas_cfdi', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('sucursal_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notas_venta_renta', function (Blueprint $table) {
            if (Schema::hasColumn('notas_venta_renta', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            if (Schema::hasColumn('notas_venta_renta', 'sucursal_id')) {
                $table->dropConstrainedForeignId('sucursal_id');
            }
        });

        Schema::table('notas_venta_venta', function (Blueprint $table) {
            if (Schema::hasColumn('notas_venta_venta', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            if (Schema::hasColumn('notas_venta_venta', 'sucursal_id')) {
                $table->dropConstrainedForeignId('sucursal_id');
            }
        });

        Schema::table('facturas_cfdi', function (Blueprint $table) {
            if (Schema::hasColumn('facturas_cfdi', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            if (Schema::hasColumn('facturas_cfdi', 'sucursal_id')) {
                $table->dropConstrainedForeignId('sucursal_id');
            }
        });
    }
};
