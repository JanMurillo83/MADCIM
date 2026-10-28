<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('sat_uso_cfdi')) {
            $usos = [
                ['G01', 'Adquisición de mercancías', true, true],
                ['G02', 'Devoluciones, descuentos o bonificaciones', true, true],
                ['G03', 'Gastos en general', true, true],
                ['I01', 'Construcciones', true, true],
                ['I02', 'Mobiliario y equipo de oficina por inversiones', true, true],
                ['I03', 'Equipo de transporte', true, true],
                ['I04', 'Equipo de cómputo y accesorios', true, true],
                ['I05', 'Dados, troqueles, moldes, matrices y herramental', true, true],
                ['I06', 'Comunicaciones telefónicas', true, true],
                ['I07', 'Comunicaciones satelitales', true, true],
                ['I08', 'Otra maquinaria y equipo', true, true],
                ['D01', 'Honorarios médicos, dentales y gastos hospitalarios', true, false],
                ['D02', 'Gastos médicos por incapacidad o discapacidad', true, false],
                ['D03', 'Gastos funerales', true, false],
                ['D04', 'Donativos', true, false],
                ['D05', 'Intereses reales efectivamente pagados por créditos hipotecarios', true, false],
                ['D06', 'Aportaciones voluntarias al SAR', true, false],
                ['D07', 'Primas por seguros de gastos médicos', true, false],
                ['D08', 'Gastos de transportación escolar obligatoria', true, false],
                ['D09', 'Depósitos en cuentas para el ahorro', true, false],
                ['D10', 'Pagos por servicios educativos', true, false],
                ['S01', 'Sin efectos fiscales', true, true],
                ['CP01', 'Pagos', true, true],
                ['CN01', 'Nómina', true, false],
            ];

            DB::table('sat_uso_cfdi')->upsert(
                array_map(static fn (array $uso): array => [
                    'clave' => $uso[0],
                    'descripcion' => $uso[1],
                    'aplica_fisica' => $uso[2],
                    'aplica_moral' => $uso[3],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $usos),
                ['clave'],
                ['descripcion', 'aplica_fisica', 'aplica_moral', 'updated_at']
            );
        }

        if (Schema::hasTable('sat_forma_pago')) {
            $formasPago = [
                ['01', 'Efectivo', false],
                ['02', 'Cheque nominativo', true],
                ['03', 'Transferencia electrónica de fondos', true],
                ['04', 'Tarjeta de crédito', true],
                ['05', 'Monedero electrónico', true],
                ['06', 'Dinero electrónico', true],
                ['08', 'Vales de despensa', false],
                ['12', 'Dación en pago', false],
                ['13', 'Pago por subrogación', false],
                ['14', 'Pago por consignación', false],
                ['15', 'Condonación', false],
                ['17', 'Compensación', false],
                ['23', 'Novación', false],
                ['24', 'Confusión', false],
                ['25', 'Remisión de deuda', false],
                ['26', 'Prescripción o caducidad', false],
                ['27', 'A satisfacción del acreedor', false],
                ['28', 'Tarjeta de débito', true],
                ['29', 'Tarjeta de servicios', true],
                ['30', 'Aplicación de anticipos', false],
                ['31', 'Intermediario pagos', true],
                ['99', 'Por definir', false],
            ];

            DB::table('sat_forma_pago')->upsert(
                array_map(static fn (array $forma): array => [
                    'clave' => $forma[0],
                    'descripcion' => $forma[1],
                    'bancarizado' => $forma[2],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $formasPago),
                ['clave'],
                ['descripcion', 'bancarizado', 'updated_at']
            );
        }

        if (Schema::hasTable('sat_metodo_pago')) {
            DB::table('sat_metodo_pago')->upsert(
                [
                    ['clave' => 'PUE', 'descripcion' => 'Pago en una sola exhibición', 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => 'PPD', 'descripcion' => 'Pago en parcialidades o diferido', 'created_at' => $now, 'updated_at' => $now],
                ],
                ['clave'],
                ['descripcion', 'updated_at']
            );
        }

        if (Schema::hasTable('sat_regimen_fiscal')) {
            $regimenes = [
                ['601', 'General de Ley Personas Morales'],
                ['603', 'Personas Morales con Fines no Lucrativos'],
                ['605', 'Sueldos y Salarios e Ingresos Asimilados a Salarios'],
                ['606', 'Arrendamiento'],
                ['607', 'Régimen de Enajenación o Adquisición de Bienes'],
                ['608', 'Demás ingresos'],
                ['610', 'Residentes en el Extranjero sin Establecimiento Permanente en México'],
                ['611', 'Ingresos por Dividendos (socios y accionistas)'],
                ['612', 'Personas Físicas con Actividades Empresariales y Profesionales'],
                ['614', 'Ingresos por intereses'],
                ['615', 'Régimen de los ingresos por obtención de premios'],
                ['616', 'Sin obligaciones fiscales'],
                ['620', 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos'],
                ['621', 'Incorporación Fiscal'],
                ['622', 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras'],
                ['623', 'Opcional para Grupos de Sociedades'],
                ['624', 'Coordinados'],
                ['625', 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas'],
                ['626', 'Régimen Simplificado de Confianza'],
            ];

            DB::table('sat_regimen_fiscal')->upsert(
                array_map(static fn (array $regimen): array => [
                    'clave' => $regimen[0],
                    'descripcion' => $regimen[1],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $regimenes),
                ['clave'],
                ['descripcion', 'updated_at']
            );
        }

        if (Schema::hasTable('sat_motivo_cancelacion')) {
            DB::table('sat_motivo_cancelacion')->upsert(
                [
                    ['clave' => '01', 'descripcion' => 'Comprobante emitido con errores con relación', 'requiere_folio_sustitucion' => true, 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => '02', 'descripcion' => 'Comprobante emitido con errores sin relación', 'requiere_folio_sustitucion' => false, 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => '03', 'descripcion' => 'No se llevó a cabo la operación', 'requiere_folio_sustitucion' => false, 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => '04', 'descripcion' => 'Operación nominativa relacionada en factura global', 'requiere_folio_sustitucion' => false, 'created_at' => $now, 'updated_at' => $now],
                ],
                ['clave'],
                ['descripcion', 'requiere_folio_sustitucion', 'updated_at']
            );
        }

        if (Schema::hasTable('sat_objeto_imp')) {
            DB::table('sat_objeto_imp')->upsert(
                [
                    ['clave' => '01', 'descripcion' => 'No objeto de impuesto', 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => '02', 'descripcion' => 'Sí objeto de impuesto', 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => '03', 'descripcion' => 'Sí objeto de impuesto y no obligado al desglose', 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => '04', 'descripcion' => 'Sí objeto del impuesto y no causa impuesto', 'created_at' => $now, 'updated_at' => $now],
                ],
                ['clave'],
                ['descripcion', 'updated_at']
            );
        }

        if (Schema::hasTable('sat_impuesto')) {
            DB::table('sat_impuesto')->upsert(
                [
                    ['clave' => '001', 'descripcion' => 'ISR', 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => '002', 'descripcion' => 'IVA', 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => '003', 'descripcion' => 'IEPS', 'created_at' => $now, 'updated_at' => $now],
                ],
                ['clave'],
                ['descripcion', 'updated_at']
            );
        }

        if (Schema::hasTable('sat_tipo_factor')) {
            DB::table('sat_tipo_factor')->upsert(
                [
                    ['clave' => 'Tasa', 'descripcion' => 'Tasa', 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => 'Cuota', 'descripcion' => 'Cuota', 'created_at' => $now, 'updated_at' => $now],
                    ['clave' => 'Exento', 'descripcion' => 'Exento', 'created_at' => $now, 'updated_at' => $now],
                ],
                ['clave'],
                ['descripcion', 'updated_at']
            );
        }
    }

    public function down(): void {}
};
