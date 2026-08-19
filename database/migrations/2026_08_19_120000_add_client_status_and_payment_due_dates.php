<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table): void {
                if (! Schema::hasColumn('clientes', 'estatus_cliente')) {
                    $table->string('estatus_cliente', 20)->default('Activo')->after('saldo');
                }

                if (! Schema::hasColumn('clientes', 'desbloqueo_discrecional')) {
                    $table->boolean('desbloqueo_discrecional')->default(false)->after('estatus_cliente');
                }
            });
        }

        if (Schema::hasTable('notas_venta_renta')) {
            Schema::table('notas_venta_renta', function (Blueprint $table): void {
                if (! Schema::hasColumn('notas_venta_renta', 'fecha_vencimiento_pago')) {
                    $table->date('fecha_vencimiento_pago')->nullable()->after('fecha_vencimiento');
                }
            });
        }

        if (Schema::hasTable('notas_venta_venta')) {
            Schema::table('notas_venta_venta', function (Blueprint $table): void {
                if (! Schema::hasColumn('notas_venta_venta', 'condicion_pago')) {
                    $table->string('condicion_pago', 20)->default('contado')->after('fecha_emision');
                }

                if (! Schema::hasColumn('notas_venta_venta', 'fecha_vencimiento_pago')) {
                    $table->date('fecha_vencimiento_pago')->nullable()->after('condicion_pago');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table): void {
                if (Schema::hasColumn('clientes', 'desbloqueo_discrecional')) {
                    $table->dropColumn('desbloqueo_discrecional');
                }

                if (Schema::hasColumn('clientes', 'estatus_cliente')) {
                    $table->dropColumn('estatus_cliente');
                }
            });
        }

        if (Schema::hasTable('notas_venta_renta')) {
            Schema::table('notas_venta_renta', function (Blueprint $table): void {
                if (Schema::hasColumn('notas_venta_renta', 'fecha_vencimiento_pago')) {
                    $table->dropColumn('fecha_vencimiento_pago');
                }
            });
        }

        if (Schema::hasTable('notas_venta_venta')) {
            Schema::table('notas_venta_venta', function (Blueprint $table): void {
                if (Schema::hasColumn('notas_venta_venta', 'fecha_vencimiento_pago')) {
                    $table->dropColumn('fecha_vencimiento_pago');
                }

                if (Schema::hasColumn('notas_venta_venta', 'condicion_pago')) {
                    $table->dropColumn('condicion_pago');
                }
            });
        }
    }
};
