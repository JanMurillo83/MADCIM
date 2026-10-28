<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FacturasCfdiSatCatalogTest extends TestCase
{
    public function test_migration_seeds_objeto_imp_claves_used_by_invoice_lines(): void
    {
        $claves = DB::table('sat_objeto_imp')
            ->whereIn('clave', ['01', '02', '03', '04'])
            ->orderBy('clave')
            ->pluck('clave')
            ->all();

        $this->assertSame(['01', '02', '03', '04'], $claves);
    }
}
