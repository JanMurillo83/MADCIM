<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('productos', 'producto_base_id')) {
            Schema::table('productos', function (Blueprint $table): void {
                $table->foreignId('producto_base_id')
                    ->nullable()
                    ->after('linea')
                    ->constrained('productos')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('historial_precios_madera')) {
            Schema::create('historial_precios_madera', function (Blueprint $table): void {
                $table->id();
                $table->uuid('lote_id')->index();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
                $table->foreignId('producto_base_id')->nullable()->constrained('productos')->nullOnDelete();
                $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('tipo', 20);
                $table->decimal('largo_cm', 12, 4)->nullable();
                $table->decimal('precio_renta_anterior', 18, 8)->default(0);
                $table->decimal('precio_renta_nuevo', 18, 8)->default(0);
                $table->decimal('precio_venta_anterior', 18, 8)->default(0);
                $table->decimal('precio_venta_nuevo', 18, 8)->default(0);
                $table->decimal('base_renta', 18, 8)->default(0);
                $table->decimal('base_venta', 18, 8)->default(0);
                $table->string('formula', 120)->nullable();
                $table->timestamps();
            });
        }

        $familias = [
            'POLINENTERO' => ['POLIN-'],
            'BARROTE-ENTERO' => ['BARROTE-'],
            'TABLA30-ENTERA' => ['TABLA30-'],
            'TABLA25-ENTERA' => ['TABLA25-'],
            'TABLA20-ENTERA' => ['TABLA20-'],
            'TABLA15-ENTERA' => ['TABLA15-'],
            'DUELA10-ENTERA' => ['DUELA-'],
        ];

        foreach ($familias as $baseClave => $prefijos) {
            $baseId = DB::table('productos')->where('clave', $baseClave)->value('id');
            if (! $baseId) {
                continue;
            }

            foreach ($prefijos as $prefijo) {
                DB::table('productos')
                    ->where('linea', 'MADERA')
                    ->where('clave', 'like', $prefijo . '%')
                    ->whereNull('producto_base_id')
                    ->update(['producto_base_id' => $baseId]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_precios_madera');

        if (Schema::hasColumn('productos', 'producto_base_id')) {
            Schema::table('productos', function (Blueprint $table): void {
                $table->dropForeign(['producto_base_id']);
                $table->dropColumn('producto_base_id');
            });
        }
    }
};
