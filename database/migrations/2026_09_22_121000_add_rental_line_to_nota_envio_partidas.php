<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('nota_envio_partidas', 'nota_venta_renta_partida_id')) {
            Schema::table('nota_envio_partidas', function (Blueprint $table): void {
                $table->foreignId('nota_venta_renta_partida_id')
                    ->nullable()
                    ->after('nota_envio_id')
                    ->constrained('nota_venta_renta_partidas')
                    ->nullOnDelete();
            });
        }

        DB::table('nota_envio_partidas as ep')
            ->join('notas_envio as e', 'e.id', '=', 'ep.nota_envio_id')
            ->whereNotNull('e.nota_venta_renta_id')
            ->whereNull('ep.nota_venta_renta_partida_id')
            ->select('ep.id', 'ep.producto_id', 'e.nota_venta_renta_id')
            ->orderBy('ep.id')
            ->each(function (object $envioPartida): void {
                $partidaId = DB::table('nota_venta_renta_partidas')
                    ->where('nota_venta_renta_id', $envioPartida->nota_venta_renta_id)
                    ->where('item', (string) $envioPartida->producto_id)
                    ->orderBy('id')
                    ->value('id');

                if ($partidaId) {
                    DB::table('nota_envio_partidas')
                        ->where('id', $envioPartida->id)
                        ->update(['nota_venta_renta_partida_id' => $partidaId]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('nota_envio_partidas', 'nota_venta_renta_partida_id')) {
            Schema::table('nota_envio_partidas', function (Blueprint $table): void {
                $table->dropForeign(['nota_venta_renta_partida_id']);
                $table->dropColumn('nota_venta_renta_partida_id');
            });
        }
    }
};
