<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('registro_rentas', 'nota_envio_partida_id')) {
            Schema::table('registro_rentas', function (Blueprint $table): void {
                $table->foreignId('nota_envio_partida_id')
                    ->nullable()
                    ->after('nota_venta_renta_id')
                    ->constrained('nota_envio_partidas')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('nota_devolucion_renta_partidas', 'registro_renta_id')) {
            Schema::table('nota_devolucion_renta_partidas', function (Blueprint $table): void {
                $table->foreignId('registro_renta_id')
                    ->nullable()
                    ->after('nota_envio_partida_id')
                    ->constrained('registro_rentas')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('cierres_devolucion_renta', 'nota_ids')) {
            Schema::table('cierres_devolucion_renta', function (Blueprint $table): void {
                $table->json('nota_ids')->nullable()->after('direccion_entrega_id');
            });
        }

        try {
            Schema::table('cierres_devolucion_renta', function (Blueprint $table): void {
                $table->dropUnique('cierres_devolucion_renta_cliente_id_direccion_entrega_id_unique');
            });
        } catch (Throwable) {
            // The unique index may already be absent in a legacy installation.
        }

        $asignadas = [];
        DB::table('registro_rentas')
            ->whereNull('nota_envio_partida_id')
            ->orderBy('id')
            ->each(function (object $registro) use (&$asignadas): void {
                $asignadasPorNotaProducto = $asignadas[$registro->nota_venta_renta_id . ':' . $registro->producto_id] ?? [];
                $partidaId = DB::table('nota_envio_partidas as envio_partidas')
                    ->join('notas_envio as envios', 'envios.id', '=', 'envio_partidas.nota_envio_id')
                    ->where('envios.nota_venta_renta_id', $registro->nota_venta_renta_id)
                    ->where('envio_partidas.producto_id', $registro->producto_id)
                    ->when($asignadasPorNotaProducto !== [], fn ($query) => $query->whereNotIn('envio_partidas.id', $asignadasPorNotaProducto))
                    ->orderBy('envio_partidas.id')
                    ->value('envio_partidas.id');

                if ($partidaId) {
                    $asignadas[$registro->nota_venta_renta_id . ':' . $registro->producto_id][] = $partidaId;
                    DB::table('registro_rentas')
                        ->where('id', $registro->id)
                        ->update(['nota_envio_partida_id' => $partidaId]);
                }
            });

        DB::table('nota_devolucion_renta_partidas')
            ->whereNull('registro_renta_id')
            ->whereNotNull('nota_envio_partida_id')
            ->orderBy('id')
            ->each(function (object $partida): void {
                $registroId = DB::table('registro_rentas')
                    ->where('nota_envio_partida_id', $partida->nota_envio_partida_id)
                    ->orderBy('id')
                    ->value('id');

                if ($registroId) {
                    DB::table('nota_devolucion_renta_partidas')
                        ->where('id', $partida->id)
                        ->update(['registro_renta_id' => $registroId]);
                }
            });

        DB::table('cierres_devolucion_renta')
            ->orderBy('id')
            ->each(function (object $cierre): void {
                $query = DB::table('notas_venta_renta')
                    ->where('cliente_id', $cierre->cliente_id)
                    ->where('direccion_entrega_id', $cierre->direccion_entrega_id);

                if ($cierre->cerrada_en) {
                    $query->where('created_at', '<=', $cierre->cerrada_en);
                }

                $notaIds = $query->pluck('id')->values()->all();

                DB::table('cierres_devolucion_renta')
                    ->where('id', $cierre->id)
                    ->update(['nota_ids' => json_encode($notaIds)]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('nota_devolucion_renta_partidas', 'registro_renta_id')) {
            Schema::table('nota_devolucion_renta_partidas', function (Blueprint $table): void {
                $table->dropForeign(['registro_renta_id']);
                $table->dropColumn('registro_renta_id');
            });
        }

        if (Schema::hasColumn('registro_rentas', 'nota_envio_partida_id')) {
            Schema::table('registro_rentas', function (Blueprint $table): void {
                $table->dropForeign(['nota_envio_partida_id']);
                $table->dropColumn('nota_envio_partida_id');
            });
        }

        if (Schema::hasColumn('cierres_devolucion_renta', 'nota_ids')) {
            Schema::table('cierres_devolucion_renta', function (Blueprint $table): void {
                $table->dropColumn('nota_ids');
                $table->unique(['cliente_id', 'direccion_entrega_id']);
            });
        }
    }
};
