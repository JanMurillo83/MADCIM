<?php

namespace App\Services;

use App\Models\CfdiPartidaImpuesto;
use App\Models\Configuracion;
use App\Models\FacturaCfdiPartidas;
use App\Models\FacturasCfdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use XMLWriter;

class FacturaCfdiXmlBuilder
{
    public function construir(FacturasCfdi $factura, Configuracion $configuracion): string
    {
        $factura->loadMissing(['cliente', 'partidas.producto', 'partidas.impuestos']);

        $emisorRfc = strtoupper(trim((string) $configuracion->rfc));
        $emisorNombre = mb_strtoupper(trim((string) $configuracion->razon_social), 'UTF-8');
        $regimenEmisor = trim((string) $configuracion->regimen);
        $lugarExpedicion = trim((string) $configuracion->codigo);
        $receptorRfc = strtoupper(trim((string) $factura->rfc_receptor));
        $receptorNombre = trim((string) $factura->razon_social_receptor);
        $receptorCodigoPostal = trim((string) ($factura->cliente?->codigo ?: $factura->domicilio_fiscal_receptor));
        $receptorRegimen = trim((string) $factura->regimen_fiscal_receptor);
        $usoCfdi = trim((string) $factura->uso_cfdi);
        $moneda = strtoupper(trim((string) ($factura->moneda ?: 'MXN')));
        $decimalesMoneda = $this->decimalesMoneda($moneda);

        foreach ([
            'RFC del emisor' => $emisorRfc,
            'razón social del emisor' => $emisorNombre,
            'régimen fiscal del emisor' => $regimenEmisor,
            'código postal de expedición' => $lugarExpedicion,
            'RFC del receptor' => $receptorRfc,
            'razón social del receptor' => $receptorNombre,
            'código postal del receptor' => $receptorCodigoPostal,
            'régimen fiscal del receptor' => $receptorRegimen,
            'uso CFDI' => $usoCfdi,
            'forma de pago' => (string) $factura->forma_pago,
            'método de pago' => (string) $factura->metodo_pago,
        ] as $campo => $valor) {
            if ($valor === '') {
                throw new RuntimeException("Falta {$campo} para construir el CFDI.");
            }
        }

        if ($factura->partidas->isEmpty()) {
            throw new RuntimeException('La factura debe contener al menos una partida para timbrarse.');
        }

        $certificadoPath = trim((string) $configuracion->sello_cer);
        if ($certificadoPath === '' || ! Storage::disk('local')->exists($certificadoPath)) {
            throw new RuntimeException('No se encontró el certificado CSD configurado para el emisor.');
        }

        $certificadoDer = Storage::disk('local')->get($certificadoPath);
        $certificadoBase64 = base64_encode($certificadoDer);
        $noCertificado = $this->extraerNumeroCertificado($certificadoDer);

        $conceptos = [];
        $subtotal = 0.0;
        $descuento = 0.0;
        $impuestosTrasladados = [];
        $impuestosRetenidos = [];

        foreach ($factura->partidas as $partida) {
            $claveProdServ = trim((string) ($partida->clave_prod_serv ?: $partida->producto?->clave_prod_serv));
            $claveUnidad = trim((string) ($partida->clave_unidad ?: $partida->producto?->clave_unidad));

            if ($claveProdServ === '') {
                throw new RuntimeException("La partida {$partida->id} no tiene clave SAT de producto o servicio.");
            }

            if ($claveUnidad === '') {
                throw new RuntimeException("La partida {$partida->id} no tiene clave SAT de unidad.");
            }

            if (! filled($partida->descripcion) || (float) $partida->cantidad <= 0) {
                throw new RuntimeException("La partida {$partida->id} no tiene descripción o cantidad válida.");
            }

            $importe = round((float) $partida->subtotal, $decimalesMoneda);
            $descuentoPartida = round((float) $partida->descuento, $decimalesMoneda);
            $base = max($importe - $descuentoPartida, 0);
            $cantidad = (float) $partida->cantidad;
            $valorUnitario = (float) $partida->valor_unitario;

            if (round($cantidad * $valorUnitario, $decimalesMoneda) !== $importe) {
                $valorUnitario = round($importe / $cantidad, 6);
            }

            $subtotal += $importe;
            $descuento += $descuentoPartida;

            $impuestos = $this->impuestosPartida($partida, $base, $decimalesMoneda);
            $impuestosPartida = array_sum(array_column($impuestos, 'importe'));
            foreach ($impuestos as $impuesto) {
                $grupo = $impuesto['tipo'] === 'Retencion' ? $impuestosRetenidos : $impuestosTrasladados;
                $clave = implode('|', [
                    $impuesto['impuesto'],
                    $impuesto['tipo_factor'],
                    $impuesto['tasa_o_cuota'] === null ? '' : $this->decimal($impuesto['tasa_o_cuota'], 6),
                ]);
                $grupo[$clave] ??= [
                    ...$impuesto,
                    'base' => 0.0,
                    'importe' => 0.0,
                ];
                $grupo[$clave]['base'] += $impuesto['base'];
                $grupo[$clave]['importe'] += $impuesto['importe'];

                if ($impuesto['tipo'] === 'Retencion') {
                    $impuestosRetenidos = $grupo;
                } else {
                    $impuestosTrasladados = $grupo;
                }
            }

            $conceptos[] = [
                'partida' => $partida,
                'clave_prod_serv' => $claveProdServ,
                'clave_unidad' => $claveUnidad,
                'valor_unitario' => $valorUnitario,
                'importe' => $importe,
                'descuento' => $descuentoPartida,
                'base' => $base,
                'impuestos' => $impuestos,
                'impuestos_total' => $impuestosPartida,
            ];
        }

        $impuestosTrasladadosTotal = round(array_sum(array_column($impuestosTrasladados, 'importe')), $decimalesMoneda);
        $impuestosRetenidosTotal = round(array_sum(array_column($impuestosRetenidos, 'importe')), $decimalesMoneda);
        $subtotal = round($subtotal, $decimalesMoneda);
        $descuento = round($descuento, $decimalesMoneda);
        $total = round($subtotal - $descuento + $impuestosTrasladadosTotal - $impuestosRetenidosTotal, $decimalesMoneda);
        $fecha = $factura->fecha_emision
            ? $factura->fecha_emision->copy()->timezone('America/Mexico_City')
            : now('America/Mexico_City');
        $fechaLimite = now('America/Mexico_City')->subMinutes(2);
        if ($fecha->greaterThan($fechaLimite)) {
            $fecha = $fechaLimite;
        }

        $xml = new XMLWriter;
        if (! $xml->openMemory()) {
            throw new RuntimeException('No fue posible iniciar la generación del XML CFDI.');
        }

        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('cfdi:Comprobante');
        $xml->writeAttribute('xmlns:cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->writeAttribute('xsi:schemaLocation', 'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd');
        $xml->writeAttribute('Version', '4.0');

        if (filled($factura->serie)) {
            $xml->writeAttribute('Serie', (string) $factura->serie);
        }
        if (filled($factura->folio)) {
            $xml->writeAttribute('Folio', (string) $factura->folio);
        }

        $xml->writeAttribute('Fecha', $fecha->format('Y-m-d\TH:i:s'));
        $xml->writeAttribute('FormaPago', (string) $factura->forma_pago);
        $xml->writeAttribute('NoCertificado', $noCertificado);
        $xml->writeAttribute('Certificado', $certificadoBase64);
        $xml->writeAttribute('Sello', '');
        $xml->writeAttribute('SubTotal', $this->decimal($subtotal, $decimalesMoneda));
        if ($descuento > 0) {
            $xml->writeAttribute('Descuento', $this->decimal($descuento, $decimalesMoneda));
        }
        $xml->writeAttribute('Moneda', $moneda);
        if ($moneda !== 'MXN') {
            $tipoCambio = (float) $factura->tipo_cambio;
            $xml->writeAttribute('TipoCambio', $this->decimal($tipoCambio > 0 ? $tipoCambio : 1, 6));
        }
        $xml->writeAttribute('Total', $this->decimal($total, $decimalesMoneda));
        $xml->writeAttribute('TipoDeComprobante', (string) ($factura->tipo_comprobante ?: 'I'));
        $xml->writeAttribute('Exportacion', (string) ($factura->exportacion ?: '01'));
        $xml->writeAttribute('MetodoPago', (string) $factura->metodo_pago);
        $xml->writeAttribute('LugarExpedicion', $lugarExpedicion);
        if (filled($factura->condiciones_pago)) {
            $xml->writeAttribute('CondicionesDePago', (string) $factura->condiciones_pago);
        }

        $xml->startElement('cfdi:Emisor');
        $xml->writeAttribute('Rfc', $emisorRfc);
        $xml->writeAttribute('Nombre', $emisorNombre);
        $xml->writeAttribute('RegimenFiscal', $regimenEmisor);
        $xml->endElement();

        $xml->startElement('cfdi:Receptor');
        $xml->writeAttribute('Rfc', $receptorRfc);
        $xml->writeAttribute('Nombre', $receptorNombre);
        $xml->writeAttribute('DomicilioFiscalReceptor', $receptorCodigoPostal);
        $xml->writeAttribute('RegimenFiscalReceptor', $receptorRegimen);
        $xml->writeAttribute('UsoCFDI', $usoCfdi);
        $xml->endElement();

        $xml->startElement('cfdi:Conceptos');
        foreach ($conceptos as $concepto) {
            /** @var FacturaCfdiPartidas $partida */
            $partida = $concepto['partida'];
            $xml->startElement('cfdi:Concepto');
            $xml->writeAttribute('ClaveProdServ', $concepto['clave_prod_serv']);
            if (filled($partida->no_identificacion)) {
                $xml->writeAttribute('NoIdentificacion', (string) $partida->no_identificacion);
            }
            $xml->writeAttribute('Cantidad', $this->decimal((float) $partida->cantidad, 6));
            $xml->writeAttribute('ClaveUnidad', $concepto['clave_unidad']);
            $unidad = trim((string) ($partida->unidad ?: $partida->producto?->unidad_sat));
            if ($unidad !== '') {
                $xml->writeAttribute('Unidad', $unidad);
            }
            $xml->writeAttribute('Descripcion', (string) $partida->descripcion);
            $xml->writeAttribute('ValorUnitario', $this->decimal($concepto['valor_unitario'], 6));
            $xml->writeAttribute('Importe', $this->decimal($concepto['importe'], $decimalesMoneda));
            if ($concepto['descuento'] > 0) {
                $xml->writeAttribute('Descuento', $this->decimal($concepto['descuento'], $decimalesMoneda));
            }
            $xml->writeAttribute('ObjetoImp', (string) ($partida->objeto_imp ?: ($concepto['impuestos'] === [] ? '01' : '02')));

            if ($concepto['impuestos'] !== []) {
                $this->escribirImpuestosConcepto($xml, $concepto['impuestos'], $decimalesMoneda);
            }

            $xml->endElement();
        }
        $xml->endElement();

        if ($impuestosTrasladados !== [] || $impuestosRetenidos !== []) {
            $xml->startElement('cfdi:Impuestos');
            $trasladadosTotal = round(array_sum(array_column($impuestosTrasladados, 'importe')), $decimalesMoneda);
            $retenidosTotal = round(array_sum(array_column($impuestosRetenidos, 'importe')), $decimalesMoneda);
            if ($retenidosTotal > 0) {
                $xml->writeAttribute('TotalImpuestosRetenidos', $this->decimal($retenidosTotal, $decimalesMoneda));
            }
            if ($trasladadosTotal > 0) {
                $xml->writeAttribute('TotalImpuestosTrasladados', $this->decimal($trasladadosTotal, $decimalesMoneda));
            }
            if ($impuestosRetenidos !== []) {
                $this->escribirResumenImpuestos($xml, 'Retenciones', 'Retencion', $impuestosRetenidos, $decimalesMoneda);
            }
            if ($impuestosTrasladados !== []) {
                $this->escribirResumenImpuestos($xml, 'Traslados', 'Traslado', $impuestosTrasladados, $decimalesMoneda);
            }
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        $contenido = $xml->outputMemory();
        if ($contenido === '') {
            throw new RuntimeException('No fue posible generar el XML CFDI.');
        }

        return $contenido;
    }

    /**
     * @return array<int, array{tipo: string, impuesto: string, tipo_factor: string, tasa_o_cuota: ?float, base: float, importe: float}>
     */
    private function impuestosPartida(FacturaCfdiPartidas $partida, float $base, int $decimalesMoneda): array
    {
        $registros = $partida->relationLoaded('impuestos')
            ? $partida->getRelation('impuestos')
            : $partida->impuestos()->get();
        if ($registros->isNotEmpty()) {
            return $registros->map(fn (CfdiPartidaImpuesto $impuesto): array => [
                'tipo' => $this->normalizarTipoImpuesto((string) $impuesto->tipo),
                'impuesto' => (string) ($impuesto->impuesto ?: '002'),
                'tipo_factor' => ucfirst(strtolower((string) ($impuesto->tipo_factor ?: 'Tasa'))),
                'tasa_o_cuota' => $impuesto->tasa_o_cuota === null ? null : (float) $impuesto->tasa_o_cuota,
                'base' => round((float) ($impuesto->base ?: $base), 6),
                'importe' => round((float) $impuesto->importe, 6),
            ])->all();
        }

        if ($base <= 0) {
            return [];
        }

        $importe = round((float) $partida->impuestos, $decimalesMoneda);
        $producto = $partida->producto;
        $impuesto = (string) ($producto?->impuesto ?: '002');
        $tipoFactor = ucfirst(strtolower((string) ($producto?->tipo_factor ?: 'Tasa')));

        if ($tipoFactor === 'Exento' && $importe > 0) {
            throw new RuntimeException("La partida {$partida->id} declara impuesto exento con un importe mayor a cero.");
        }

        if ($importe <= 0 && $partida->objeto_imp !== '02' && $producto?->objeto_imp !== '02') {
            return [];
        }

        $tasaConfigurada = $producto?->tasa_o_cuota;
        if (is_numeric($tasaConfigurada) && (float) $tasaConfigurada >= 0) {
            $tasa = (float) $tasaConfigurada;
        } elseif ($impuesto === '002' && $tipoFactor === 'Tasa') {
            $tasa = $importe > 0 ? 0.16 : 0.0;
        } else {
            throw new RuntimeException("La partida {$partida->id} no tiene una tasa o cuota SAT configurada.");
        }

        if ($importe > 0 && $tasa <= 0) {
            throw new RuntimeException("La partida {$partida->id} tiene importe de impuesto, pero no una tasa SAT válida.");
        }

        $importe = round($base * $tasa, 6);

        return [[
            'tipo' => 'Traslado',
            'impuesto' => $impuesto,
            'tipo_factor' => $tipoFactor,
            'tasa_o_cuota' => $tipoFactor === 'Exento' ? null : $tasa,
            'base' => round($base, 6),
            'importe' => $importe,
        ]];
    }

    /**
     * @param  array<int, array{tipo: string, impuesto: string, tipo_factor: string, tasa_o_cuota: ?float, base: float, importe: float}>  $impuestos
     */
    private function escribirImpuestosConcepto(XMLWriter $xml, array $impuestos, int $decimalesImporte): void
    {
        $traslados = array_filter($impuestos, static fn (array $impuesto): bool => $impuesto['tipo'] !== 'Retencion');
        $retenciones = array_filter($impuestos, static fn (array $impuesto): bool => $impuesto['tipo'] === 'Retencion');
        $xml->startElement('cfdi:Impuestos');

        foreach (['Retenciones' => $retenciones, 'Traslados' => $traslados] as $grupo => $items) {
            if ($items === []) {
                continue;
            }

            $xml->startElement('cfdi:'.$grupo);
            foreach ($items as $item) {
                $elemento = $item['tipo'] === 'Retencion' ? 'Retencion' : 'Traslado';
                $xml->startElement('cfdi:'.$elemento);
                $xml->writeAttribute('Base', $this->decimal($item['base'], 6));
                $xml->writeAttribute('Impuesto', $item['impuesto']);
                $xml->writeAttribute('TipoFactor', $item['tipo_factor']);
                if ($item['tipo_factor'] !== 'Exento' && $item['tasa_o_cuota'] !== null) {
                    $xml->writeAttribute('TasaOCuota', $this->decimal($item['tasa_o_cuota'], 6));
                    $xml->writeAttribute('Importe', $this->decimal($item['importe'], $decimalesImporte));
                }
                $xml->endElement();
            }
            $xml->endElement();
        }

        $xml->endElement();
    }

    /**
     * @param  array<string, array{tipo: string, impuesto: string, tipo_factor: string, tasa_o_cuota: ?float, base: float, importe: float}>  $impuestos
     */
    private function escribirResumenImpuestos(XMLWriter $xml, string $grupo, string $elemento, array $impuestos, int $decimalesMoneda): void
    {
        $xml->startElement('cfdi:'.$grupo);
        foreach ($impuestos as $impuesto) {
            $xml->startElement('cfdi:'.$elemento);
            $xml->writeAttribute('Impuesto', $impuesto['impuesto']);
            if ($grupo === 'Retenciones') {
                $xml->writeAttribute('Importe', $this->decimal($impuesto['importe'], $decimalesMoneda));
            } else {
                $xml->writeAttribute('Base', $this->decimal($impuesto['base'], $decimalesMoneda));
                $xml->writeAttribute('TipoFactor', $impuesto['tipo_factor']);
                if ($impuesto['tipo_factor'] !== 'Exento' && $impuesto['tasa_o_cuota'] !== null) {
                    $xml->writeAttribute('TasaOCuota', $this->decimal($impuesto['tasa_o_cuota'], 6));
                    $xml->writeAttribute('Importe', $this->decimal($impuesto['importe'], $decimalesMoneda));
                }
            }
            $xml->endElement();
        }
        $xml->endElement();
    }

    private function normalizarTipoImpuesto(string $tipo): string
    {
        return strtolower($tipo) === 'retencion' ? 'Retencion' : 'Traslado';
    }

    private function decimal(float $value, int $decimals = 2): string
    {
        return number_format(round($value, $decimals), $decimals, '.', '');
    }

    private function decimalesMoneda(string $moneda): int
    {
        $decimales = DB::table('sat_moneda')
            ->where('clave', $moneda)
            ->value('decimales');

        if (is_numeric($decimales) && (int) $decimales >= 0 && (int) $decimales <= 6) {
            return (int) $decimales;
        }

        if (in_array($moneda, ['MXN', 'USD'], true)) {
            return 2;
        }

        throw new RuntimeException("No se encontró la precisión decimal de la moneda {$moneda} en el catálogo SAT.");
    }

    private function extraerNumeroCertificado(string $certificadoDer): string
    {
        $pem = "-----BEGIN CERTIFICATE-----\n"
            .chunk_split(base64_encode($certificadoDer), 64, "\n")
            ."-----END CERTIFICATE-----\n";
        $datos = openssl_x509_parse($pem, false);

        if (! is_array($datos)) {
            throw new RuntimeException('El certificado CSD no tiene un formato válido.');
        }

        $serial = trim((string) ($datos['serialNumber'] ?? ''));
        if (preg_match('/^[0-9]+$/', $serial) === 1) {
            return $serial;
        }

        $hex = preg_replace('/[^0-9a-f]/i', '', (string) ($datos['serialNumberHex'] ?? ''));
        if ($hex !== '' && strlen($hex) % 2 === 0) {
            $ascii = hex2bin($hex);
            if (is_string($ascii) && preg_match('/^[0-9]+$/', $ascii) === 1) {
                return $ascii;
            }
        }

        throw new RuntimeException('No fue posible obtener el número de serie del certificado CSD.');
    }
}
