<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sucursales')
            ->orderBy('id')
            ->get(['id', 'nombre'])
            ->each(function (object $sucursal): void {
                $nombreCaja = 'Caja ' . $sucursal->nombre;

                $caja = DB::table('cajas')
                    ->where('sucursal_id', $sucursal->id)
                    ->orderBy('id')
                    ->first();

                if ($caja) {
                    if ($caja->nombre === null || $caja->nombre === 'Caja 1') {
                        DB::table('cajas')
                            ->where('id', $caja->id)
                            ->update([
                                'nombre' => $nombreCaja,
                                'updated_at' => now(),
                            ]);
                    }

                    return;
                }

                $caja = DB::table('cajas')
                    ->whereNull('sucursal_id')
                    ->orderBy('id')
                    ->first();

                if ($caja) {
                    DB::table('cajas')
                        ->where('id', $caja->id)
                        ->update([
                            'nombre' => $nombreCaja,
                            'sucursal_id' => $sucursal->id,
                            'updated_at' => now(),
                        ]);

                    return;
                }

                DB::table('cajas')->insert([
                    'nombre' => $nombreCaja,
                    'sucursal_id' => $sucursal->id,
                    'estatus' => 'Cerrada',
                    'saldo_inicial_cash' => 0,
                    'total_ingresos_cash' => 0,
                    'total_egresos_cash' => 0,
                    'total_diferencia' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('cajas')
            ->whereIn('nombre', DB::table('sucursales')->pluck('nombre')->map(
                fn (string $nombre): string => 'Caja ' . $nombre
            ))
            ->update([
                'nombre' => 'Caja 1',
                'sucursal_id' => null,
                'updated_at' => now(),
            ]);
    }
};
