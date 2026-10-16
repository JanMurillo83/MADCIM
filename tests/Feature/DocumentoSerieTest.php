<?php

namespace Tests\Feature;

use App\Models\DocumentoSerie;
use App\Models\Sucursal;
use Tests\TestCase;

class DocumentoSerieTest extends TestCase
{
    public function test_asigna_folios_independientes_por_sucursal(): void
    {
        $sucursalA = Sucursal::create(['nombre' => 'Sucursal A', 'codigo' => 'A']);
        $sucursalB = Sucursal::create(['nombre' => 'Sucursal B', 'codigo' => 'B']);

        $this->assertSame(1, DocumentoSerie::nextFolio('notas_venta_venta', 'M', $sucursalA->id));
        $this->assertSame(1, DocumentoSerie::nextFolio('notas_venta_venta', 'M', $sucursalB->id));
        $this->assertSame(2, DocumentoSerie::nextFolio('notas_venta_venta', 'M', $sucursalA->id));
    }
}
