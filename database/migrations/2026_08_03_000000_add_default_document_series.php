<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
            'pagos',
            'requisiciones_compra',
            'ordenes_compra',
            'recepciones_compra',
        ];

        foreach ($documentos as $documentoTipo) {
            foreach ($series as $serie) {
                DB::table('documento_series')->updateOrInsert(
                    [
                        'documento_tipo' => $documentoTipo,
                        'serie' => $serie['serie'],
                    ],
                    [
                        'descripcion' => $serie['descripcion'],
                        'updated_at' => now(),
                    ],
                );
            }
        }

        DB::table('documento_series')->updateOrInsert(
            [
                'documento_tipo' => 'notas_devolucion_renta',
                'serie' => 'NDR',
            ],
            [
                'descripcion' => 'NOTA DE DEVOLUCION DE RENTA',
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        // Las series existentes pueden tener folios utilizados; no se eliminan al revertir.
    }
};
