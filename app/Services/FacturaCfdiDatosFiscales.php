<?php

namespace App\Services;

use App\Models\Clientes;
use App\Models\Configuracion;

class FacturaCfdiDatosFiscales
{
    /**
     * @return array{nombre_emisor: ?string, rfc_emisor: ?string, regimen_fiscal_emisor: ?string, lugar_expedicion: ?string}
     */
    public function datosEmisor(?Configuracion $configuracion): array
    {
        return [
            'nombre_emisor' => $configuracion?->razon_social,
            'rfc_emisor' => $configuracion?->rfc,
            'regimen_fiscal_emisor' => $configuracion?->regimen,
            'lugar_expedicion' => $configuracion?->codigo,
        ];
    }

    /**
     * @return array{razon_social_receptor: ?string, rfc_receptor: ?string, regimen_fiscal_receptor: ?string, domicilio_fiscal_receptor: ?string}
     */
    public function datosReceptor(?Clientes $cliente): array
    {
        return [
            'razon_social_receptor' => $cliente?->nombre,
            'rfc_receptor' => $cliente?->rfc,
            'regimen_fiscal_receptor' => $cliente?->regimen,
            'domicilio_fiscal_receptor' => $cliente?->codigo,
        ];
    }
}
