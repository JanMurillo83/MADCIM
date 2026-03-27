<?php

namespace App\Services;

use App\Models\Productos;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductosImportService
{
    public const HEADERS = [
        'clave',
        'descripcion',
        'm2_cubre',
        'costo',
        'ultimo_costo',
        'precio_venta',
        'precio_renta_mes',
        'precio_renta_dia',
        'precio_renta_semana',
        'existencia',
        'grupo',
        'linea',
        'largo',
        'ancho',
    ];

    private const REQUIRED_FIELDS = [
        'clave',
        'descripcion',
        'grupo',
        'linea',
    ];

    private const NUMERIC_DEFAULTS = [
        'm2_cubre' => 0,
        'costo' => 0,
        'ultimo_costo' => 0,
        'precio_venta' => 0,
        'precio_renta_mes' => 0,
        'precio_renta_dia' => 0,
        'precio_renta_semana' => 0,
        'existencia' => 0,
        'largo' => 0,
        'ancho' => 0,
    ];

    public function importFromPath(string $path): array
    {
        $rows = $this->readRows($path);

        if (count($rows) === 0) {
            return [0, 0];
        }

        $headers = array_shift($rows);
        $map = $this->mapHeaders($headers);

        $insertados = 0;
        $actualizados = 0;

        DB::transaction(function () use ($rows, $map, &$insertados, &$actualizados) {
            foreach ($rows as $index => $row) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $data = $this->buildData($row, $map, $index + 2);

                $producto = Productos::updateOrCreate(
                    ['clave' => $data['clave']],
                    $data,
                );

                if ($producto->wasRecentlyCreated) {
                    $insertados += 1;
                } else {
                    $actualizados += 1;
                }
            }
        });

        return [$insertados, $actualizados];
    }

    private function readRows(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->readCsv($path);
        }

        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            throw new \RuntimeException('Formato no soportado. Usa .xlsx, .xls o .csv.');
        }

        if (!class_exists(IOFactory::class)) {
            throw new \RuntimeException('Falta instalar phpoffice/phpspreadsheet para leer Excel.');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray();
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el archivo.');
        }

        while (($data = fgetcsv($handle, 200000, ',')) !== false) {
            $rows[] = $data;
        }

        fclose($handle);

        return $rows;
    }

    private function mapHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $index => $header) {
            $key = $this->normalizeHeader($header);

            if ($key !== '') {
                $normalized[$key] = $index;
            }
        }

        $missing = array_diff(self::HEADERS, array_keys($normalized));

        if (!empty($missing)) {
            throw new \RuntimeException('Faltan columnas: ' . implode(', ', $missing));
        }

        $map = [];

        foreach (self::HEADERS as $header) {
            $map[$header] = $normalized[$header];
        }

        return $map;
    }

    private function normalizeHeader(mixed $header): string
    {
        $header = strtolower(trim((string) $header));

        if (str_starts_with($header, "\xEF\xBB\xBF")) {
            $header = substr($header, 3);
        }

        return str_replace(' ', '_', $header);
    }

    private function buildData(array $row, array $map, int $line): array
    {
        $data = [];

        foreach (self::HEADERS as $header) {
            $value = $row[$map[$header]] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '') {
                $value = null;
            }

            $data[$header] = $value;
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            if ($data[$field] === null) {
                throw new \RuntimeException("Campo {$field} requerido en la fila {$line}.");
            }
        }

        foreach (self::NUMERIC_DEFAULTS as $field => $default) {
            if ($data[$field] === null) {
                $data[$field] = $default;
            }

            $data[$field] = $this->castNumeric($data[$field], $field, $line);
        }

        return $data;
    }

    private function castNumeric(mixed $value, string $field, int $line): float
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        if (!is_numeric($value)) {
            throw new \RuntimeException("Valor invalido para {$field} en la fila {$line}.");
        }

        return (float) $value;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
