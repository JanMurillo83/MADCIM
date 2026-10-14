<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('existencias_sucursal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->decimal('existencia', 18, 8)->default(0);
            $table->timestamps();

            $table->unique(['producto_id', 'sucursal_id']);
        });

        $sucursalIds = DB::table('sucursales')->orderBy('id')->pluck('id')->all();
        $productos = DB::table('productos')->orderBy('id')->get(['id', 'existencia']);
        if ($sucursalIds) {
            foreach ($productos as $producto) {
                foreach ($sucursalIds as $index => $sucursalId) {
                    DB::table('existencias_sucursal')->insert([
                        'producto_id' => $producto->id,
                        'sucursal_id' => $sucursalId,
                        'existencia' => $index === 0 ? $producto->existencia : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('existencias_sucursal');
    }
};
