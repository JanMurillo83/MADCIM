<?php

namespace Tests\Feature;

use App\Models\DispositivoBiometrico;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispositivoBiometricoTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispositivo_puede_crearse_sin_token_adms(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);

        $dispositivo = DispositivoBiometrico::create([
            'nombre' => 'Reloj MB160ID',
            'numero_serie' => 'SIMMB160ID001',
            'modelo' => 'ZKTeco MB160ID',
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);

        $this->assertNull($dispositivo->token_hash);
        $this->assertDatabaseHas('dispositivos_biometricos', [
            'numero_serie' => 'SIMMB160ID001',
            'token_hash' => null,
        ]);
    }
}
