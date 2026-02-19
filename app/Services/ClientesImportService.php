<?php

namespace App\Services;

use App\Models\Clientes;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClientesImportService
{
    public const HEADERS = [
        'clave',
        'nombre',
        'rfc',
        'regimen',
        'codigo',
        'calle',
        'exterior',
        'interior',
        'colonia',
        'municipio',
        'estado',
        'pais',
        'telefono',
        'correo',
        'descuento',
        'lista',
        'contacto',
        'dias_credito',
        'saldo',
    ];

    private const REQUIRED_FIELDS = [
        'clave',
        'nombre',
        'rfc',
        'regimen',
        'codigo',
        'telefono',
        'correo',
        'contacto',
    ];

    private const DEFAULTS = [
        'descuento' => 0,
        'lista' => 1,
        'dias_credito' => 0,
        'saldo' => 0,
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

                $cliente = Clientes::updateOrCreate(
                    ['clave' => $data['clave']],
                    $data
                );

                if ($cliente->wasRecentlyCreated) {
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
            throw new \RuntimeException('Formato no soportado. Usa .xlsx o .csv.');
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

    private function normalizeHeader(?string $header): string
    {
        $header = strtolower(trim((string) $header));
        $header = str_replace(' ', '_', $header);

        return $header;
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

        foreach (self::DEFAULTS as $field => $default) {
            if ($data[$field] === null) {
                $data[$field] = $default;
            }
        }

        $data['descuento'] = $this->castNumeric($data['descuento'], 'descuento', $line);
        $data['lista'] = $this->castInteger($data['lista'], 'lista', $line);
        $data['dias_credito'] = $this->castInteger($data['dias_credito'], 'dias_credito', $line);
        $data['saldo'] = $this->castNumeric($data['saldo'], 'saldo', $line);

        return $data;
    }

    private function castNumeric($value, string $field, int $line): float
    {
        if (!is_numeric($value)) {
            throw new \RuntimeException("Valor invalido para {$field} en la fila {$line}.");
        }

        return (float) $value;
    }

    private function castInteger($value, string $field, int $line): int
    {
        if (!is_numeric($value)) {
            throw new \RuntimeException("Valor invalido para {$field} en la fila {$line}.");
        }

        return (int) $value;
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
