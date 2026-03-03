<?php

namespace Tests\Feature;

use Database\Seeders\SatRegimenFiscalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SatRegimenFiscalSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_crea_el_catalogo_de_regimen_fiscal_de_sat(): void
    {
        $this->seed(SatRegimenFiscalSeeder::class);

        $count = DB::table('sat_regimen_fiscal')->count();
        $this->assertSame(19, $count);

        $this->assertDatabaseHas('sat_regimen_fiscal', [
            'clave' => '601',
            'descripcion' => 'General de Ley Personas Morales',
        ]);

        // Idempotencia
        $this->seed(SatRegimenFiscalSeeder::class);
        $count2 = DB::table('sat_regimen_fiscal')->count();
        $this->assertSame(19, $count2);
    }
}
