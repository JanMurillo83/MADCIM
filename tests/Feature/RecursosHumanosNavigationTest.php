<?php

namespace Tests\Feature;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\DispositivosBiometricos\DispositivoBiometricoResource;
use App\Filament\Clusters\RecursosHumanos\Resources\VinculacionesBiometricas\VinculacionBiometricaResource;
use App\Support\RolePermissions;
use Filament\Facades\Filament;
use Tests\TestCase;

class RecursosHumanosNavigationTest extends TestCase
{
    public function test_cluster_permanece_registrado_pero_oculto_en_la_navegacion(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertContains(RecursosHumanosCluster::class, $panel->getClusters());
        $this->assertFalse(RecursosHumanosCluster::shouldRegisterNavigation());
        $this->assertTrue(app('router')->has(RecursosHumanosCluster::getRouteName($panel)));
    }

    public function test_catalogos_biometricos_estan_registrados_en_el_panel_y_son_visibles_para_administrador(): void
    {
        $resources = Filament::getPanel('admin')->getResources();

        $this->assertContains(DispositivoBiometricoResource::class, $resources);
        $this->assertContains(VinculacionBiometricaResource::class, $resources);
        $this->assertSame('Catálogos', DispositivoBiometricoResource::getNavigationGroup());
        $this->assertSame('Catálogos', VinculacionBiometricaResource::getNavigationGroup());
        $this->assertTrue(RolePermissions::canAccessResource(DispositivoBiometricoResource::class, RolePermissions::ROLE_ADMIN));
        $this->assertTrue(RolePermissions::canAccessResource(VinculacionBiometricaResource::class, RolePermissions::ROLE_ADMIN));
    }

    public function test_catalogos_biometricos_exponen_listado_creacion_y_edicion(): void
    {
        $this->assertSame(['index', 'create', 'edit'], array_keys(DispositivoBiometricoResource::getPages()));
        $this->assertSame(['index', 'create', 'edit'], array_keys(VinculacionBiometricaResource::getPages()));
    }
}
