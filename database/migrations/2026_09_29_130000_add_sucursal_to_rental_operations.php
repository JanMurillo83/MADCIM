<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cotizaciones', 'sucursal_id')) {
            Schema::table('cotizaciones', function (Blueprint $table): void {
                $table->foreignId('sucursal_id')->nullable()->after('cliente_id')->constrained('sucursales')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('notas_envio', 'sucursal_id')) {
            Schema::table('notas_envio', function (Blueprint $table): void {
                $table->foreignId('sucursal_id')->nullable()->after('folio')->constrained('sucursales')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('notas_devolucion_renta', 'sucursal_id')) {
            Schema::table('notas_devolucion_renta', function (Blueprint $table): void {
                $table->foreignId('sucursal_id')->nullable()->after('nota_envio_id')->constrained('sucursales')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('devoluciones_renta', 'sucursal_id')) {
            Schema::table('devoluciones_renta', function (Blueprint $table): void {
                $table->foreignId('sucursal_id')->nullable()->after('folio')->constrained('sucursales')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('cierres_devolucion_renta', 'sucursal_id')) {
            Schema::table('cierres_devolucion_renta', function (Blueprint $table): void {
                $table->foreignId('sucursal_id')->nullable()->after('direccion_entrega_id')->constrained('sucursales')->nullOnDelete();
            });
        }

        DB::statement('UPDATE notas_envio SET sucursal_id = (SELECT sucursal_id FROM notas_venta_renta WHERE notas_venta_renta.id = notas_envio.nota_venta_renta_id) WHERE nota_venta_renta_id IS NOT NULL');
        DB::statement('UPDATE notas_envio SET sucursal_id = (SELECT sucursal_id FROM notas_venta_venta WHERE notas_venta_venta.id = notas_envio.nota_venta_venta_id) WHERE nota_venta_venta_id IS NOT NULL');
        DB::statement('UPDATE notas_devolucion_renta SET sucursal_id = (SELECT sucursal_id FROM notas_envio WHERE notas_envio.id = notas_devolucion_renta.nota_envio_id)');
        DB::statement('UPDATE devoluciones_renta SET sucursal_id = (SELECT sucursal_id FROM notas_venta_renta WHERE notas_venta_renta.id = devoluciones_renta.documento_origen_id) WHERE documento_origen_id IS NOT NULL');

        DB::table('cotizaciones')->whereNull('sucursal_id')->get(['id'])->each(function (object $cotizacion): void {
            $sucursales = DB::table('notas_venta_renta')
                ->select('sucursal_id')
                ->where('documento_origen_id', $cotizacion->id)
                ->whereNotNull('sucursal_id')
                ->union(DB::table('notas_venta_venta')
                    ->select('sucursal_id')
                    ->where('documento_origen_id', $cotizacion->id)
                    ->whereNotNull('sucursal_id'))
                ->distinct()
                ->pluck('sucursal_id')
                ->unique();

            if ($sucursales->count() === 1) {
                DB::table('cotizaciones')->where('id', $cotizacion->id)->update(['sucursal_id' => $sucursales->first()]);
            }
        });

        DB::table('cierres_devolucion_renta')->whereNull('sucursal_id')->get(['id', 'nota_ids'])->each(function (object $cierre): void {
            $notaIds = is_array($cierre->nota_ids) ? $cierre->nota_ids : json_decode((string) $cierre->nota_ids, true);
            if (!is_array($notaIds) || $notaIds === []) {
                return;
            }

            $sucursales = DB::table('notas_venta_renta')
                ->whereIn('id', $notaIds)
                ->whereNotNull('sucursal_id')
                ->distinct()
                ->pluck('sucursal_id');

            if ($sucursales->count() === 1) {
                DB::table('cierres_devolucion_renta')->where('id', $cierre->id)->update(['sucursal_id' => $sucursales->first()]);
            }
        });
        DB::statement("UPDATE cierres_devolucion_renta SET sucursal_id = (SELECT sucursal_id FROM notas_venta_venta WHERE notas_venta_venta.id = cierres_devolucion_renta.nota_venta_venta_id) WHERE nota_venta_venta_id IS NOT NULL");
    }

    public function down(): void
    {
        if (Schema::hasColumn('cotizaciones', 'sucursal_id')) {
            Schema::table('cotizaciones', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('sucursal_id');
            });
        }

        foreach (['cierres_devolucion_renta', 'devoluciones_renta', 'notas_devolucion_renta', 'notas_envio'] as $tableName) {
            if (Schema::hasColumn($tableName, 'sucursal_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->dropConstrainedForeignId('sucursal_id');
                });
            }
        }
    }
};
