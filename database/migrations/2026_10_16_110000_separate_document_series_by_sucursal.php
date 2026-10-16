<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('documento_series', 'sucursal_id')) {
            Schema::table('documento_series', function (Blueprint $table): void {
                $table->foreignId('sucursal_id')->nullable()->after('serie')->constrained('sucursales')->nullOnDelete();
            });
        }

        Schema::table('documento_series', function (Blueprint $table): void {
            $table->dropUnique(['documento_tipo', 'serie']);
            $table->unique(['documento_tipo', 'serie', 'sucursal_id']);
        });

        if (! Schema::hasColumn('devoluciones_venta', 'sucursal_id')) {
            Schema::table('devoluciones_venta', function (Blueprint $table): void {
                $table->foreignId('sucursal_id')->nullable()->after('folio')->constrained('sucursales')->nullOnDelete();
            });
        }

        DB::statement('UPDATE devoluciones_venta SET sucursal_id = (SELECT sucursal_id FROM notas_venta_venta WHERE notas_venta_venta.id = devoluciones_venta.documento_origen_id) WHERE documento_origen_id IS NOT NULL');

        foreach ([
            ['documento_tipo' => 'notas_envio', 'serie' => 'NE', 'descripcion' => 'Notas de envío'],
            ['documento_tipo' => 'devoluciones_renta', 'serie' => 'DR', 'descripcion' => 'Devoluciones de renta'],
        ] as $serie) {
            DB::table('documento_series')->insertOrIgnore([
                ...$serie,
                'ultimo_folio' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('devoluciones_venta', function (Blueprint $table): void {
            if (Schema::hasColumn('devoluciones_venta', 'sucursal_id')) {
                $table->dropConstrainedForeignId('sucursal_id');
            }
        });

        Schema::table('documento_series', function (Blueprint $table): void {
            $table->dropUnique(['documento_tipo', 'serie', 'sucursal_id']);
            $table->unique(['documento_tipo', 'serie']);
            $table->dropConstrainedForeignId('sucursal_id');
        });
    }
};
