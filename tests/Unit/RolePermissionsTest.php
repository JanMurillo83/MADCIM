<?php

namespace Tests\Unit;

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
}
