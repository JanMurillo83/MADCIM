<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatRegimenFiscalSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            ['clave' => '601', 'descripcion' => 'General de Ley Personas Morales'],
            ['clave' => '603', 'descripcion' => 'Personas Morales con Fines no Lucrativos'],
            ['clave' => '605', 'descripcion' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios'],
            ['clave' => '606', 'descripcion' => 'Arrendamiento'],
            ['clave' => '607', 'descripcion' => 'Régimen de Enajenación o Adquisición de Bienes'],
            ['clave' => '608', 'descripcion' => 'Demás ingresos'],
            ['clave' => '610', 'descripcion' => 'Residentes en el Extranjero sin Establecimiento Permanente en México'],
            ['clave' => '611', 'descripcion' => 'Ingresos por Dividendos (socios y accionistas)'],
            ['clave' => '612', 'descripcion' => 'Personas Físicas con Actividades Empresariales y Profesionales'],
            ['clave' => '614', 'descripcion' => 'Ingresos por intereses'],
            ['clave' => '615', 'descripcion' => 'Régimen de los ingresos por obtención de premios'],
            ['clave' => '616', 'descripcion' => 'Sin obligaciones fiscales'],
            ['clave' => '620', 'descripcion' => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos'],
            ['clave' => '621', 'descripcion' => 'Incorporación Fiscal'],
            ['clave' => '622', 'descripcion' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras'],
            ['clave' => '623', 'descripcion' => 'Opcional para Grupos de Sociedades'],
            ['clave' => '624', 'descripcion' => 'Coordinados'],
            ['clave' => '625', 'descripcion' => 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas'],
            ['clave' => '626', 'descripcion' => 'Régimen Simplificado de Confianza'],
        ];

        $rows = array_map(static function (array $row) use ($now): array {
            return [
                ...$row,
                'updated_at' => $now,
                'created_at' => $now,
            ];
        }, $rows);

        DB::table('sat_regimen_fiscal')->upsert(
            $rows,
            ['clave'],
            ['descripcion', 'updated_at']
        );
    }
}
