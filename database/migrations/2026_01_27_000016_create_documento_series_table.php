<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_series', function (Blueprint $table) {
            $table->id();
            $table->string('documento_tipo', 100);
            $table->string('serie', 20);
            $table->string('descripcion', 100)->nullable();
            $table->unsignedBigInteger('ultimo_folio')->default(0);
            $table->timestamps();

            $table->unique(['documento_tipo', 'serie']);
        });

        $series = [
            ['serie' => 'M', 'descripcion' => 'MADERERIA'],
            ['serie' => 'C', 'descripcion' => 'CARPINTERIA'],
            ['serie' => 'F', 'descripcion' => 'FERRETERIA'],
        ];

        $documentos = [
            'cotizaciones',
            'notas_venta_renta',
            'notas_venta_venta',
            'facturas_cfdi',
            'devoluciones_renta',
            'devoluciones_venta',
        ];

        foreach ($documentos as $documentoTipo) {
            foreach ($series as $serie) {
                DB::table('documento_series')->insert([
                    'documento_tipo' => $documentoTipo,
                    'serie' => $serie['serie'],
                    'descripcion' => $serie['descripcion'],
                    'ultimo_folio' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_series');
    }
};
