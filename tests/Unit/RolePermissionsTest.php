<?php

namespace Tests\Unit;

use App\Filament\Pages\ConsultaDevoluciones;
use App\Filament\Pages\CierresDevolucionRenta;
use App\Filament\Resources\Configuracion\ConfiguracionResource;
use App\Filament\Resources\Inventario\InventarioResource;
use App\Support\RolePermissions;
use PHPUnit\Framework\TestCase;

class RolePermissionsTest extends TestCase
{
    public function test_supervisors_can_access_inventory_movements(): void
    {
        $this->assertTrue(
            RolePermissions::canAccessResource(InventarioResource::class, RolePermissions::ROLE_SUPERVISOR)
        );
    }

    public function test_supervisors_can_access_all_non_administration_pages(): void
    {
        $this->assertTrue(
            RolePermissions::canAccessPage(ConsultaDevoluciones::class, RolePermissions::ROLE_SUPERVISOR)
        );
    }

    public function test_operational_branch_roles_can_process_returns_and_close_works(): void
    {
        foreach ([RolePermissions::ROLE_VENDEDOR, RolePermissions::ROLE_ALMACEN, RolePermissions::ROLE_ENTREGAS] as $role) {
            $this->assertTrue(RolePermissions::canAccessPage(ConsultaDevoluciones::class, $role));
            $this->assertTrue(RolePermissions::canAccessPage(CierresDevolucionRenta::class, $role));
        }
    }

    public function test_supervisors_cannot_access_administration_resources(): void
    {
        $this->assertFalse(
            RolePermissions::canAccessResource(ConfiguracionResource::class, RolePermissions::ROLE_SUPERVISOR)
        );
    }
}
