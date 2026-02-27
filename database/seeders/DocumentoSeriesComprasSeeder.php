<?php

namespace Database\Seeders;

use App\Models\DocumentoSerie;
use Illuminate\Database\Seeder;

class DocumentoSeriesComprasSeeder extends Seeder
{
    public function run(): void
    {
        $series = [
            [
                'documento_tipo' => 'requisiciones_compra',
                'serie' => 'RQ',
                'descripcion' => 'Requisiciones de compra',
            ],
            [
                'documento_tipo' => 'ordenes_compra',
                'serie' => 'OC',
                'descripcion' => 'Ordenes de compra',
            ],
            [
                'documento_tipo' => 'recepciones_compra',
                'serie' => 'RC',
                'descripcion' => 'Recepciones de compra',
            ],
        ];

        foreach ($series as $serie) {
            DocumentoSerie::firstOrCreate(
                [
                    'documento_tipo' => $serie['documento_tipo'],
                    'serie' => $serie['serie'],
                ],
                [
                    'descripcion' => $serie['descripcion'],
                    'ultimo_folio' => 0,
                ]
            );
        }
    }
}
