<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('pagos', 'serie')) {
                $table->string('serie', 10)->nullable()->after('id');
            }
            if (!Schema::hasColumn('pagos', 'folio')) {
                $table->string('folio', 50)->nullable()->after('serie');
            }
            if (!Schema::hasColumn('pagos', 'fecha_emision')) {
                $table->dateTime('fecha_emision')->nullable()->after('folio');
            }
            if (!Schema::hasColumn('pagos', 'fecha_pago_hora')) {
                $table->dateTime('fecha_pago_hora')->nullable()->after('fecha_pago');
            }
            if (!Schema::hasColumn('pagos', 'moneda')) {
                $table->string('moneda', 3)->default('MXN')->after('metodo_pago');
            }
            if (!Schema::hasColumn('pagos', 'tipo_cambio')) {
                $table->decimal('tipo_cambio', 18, 6)->default(1)->after('moneda');
            }
            if (!Schema::hasColumn('pagos', 'tipo_comprobante')) {
                $table->string('tipo_comprobante', 1)->default('P')->after('tipo_cambio');
            }
            if (!Schema::hasColumn('pagos', 'exportacion')) {
                $table->string('exportacion', 2)->default('01')->after('tipo_comprobante');
            }
            if (!Schema::hasColumn('pagos', 'lugar_expedicion')) {
                $table->string('lugar_expedicion', 5)->nullable()->after('exportacion');
            }
            if (!Schema::hasColumn('pagos', 'rfc_emisor')) {
                $table->string('rfc_emisor', 13)->nullable()->after('lugar_expedicion');
            }
            if (!Schema::hasColumn('pagos', 'nombre_emisor')) {
                $table->string('nombre_emisor')->nullable()->after('rfc_emisor');
            }
            if (!Schema::hasColumn('pagos', 'rfc_receptor')) {
                $table->string('rfc_receptor', 13)->nullable()->after('nombre_emisor');
            }
            if (!Schema::hasColumn('pagos', 'nombre_receptor')) {
                $table->string('nombre_receptor')->nullable()->after('rfc_receptor');
            }
            if (!Schema::hasColumn('pagos', 'regimen_fiscal_receptor')) {
                $table->string('regimen_fiscal_receptor', 5)->nullable()->after('nombre_receptor');
            }
            if (!Schema::hasColumn('pagos', 'domicilio_fiscal_receptor')) {
                $table->string('domicilio_fiscal_receptor', 5)->nullable()->after('regimen_fiscal_receptor');
            }
            if (!Schema::hasColumn('pagos', 'uso_cfdi')) {
                $table->string('uso_cfdi', 10)->default('CP01')->after('domicilio_fiscal_receptor');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_uuid')) {
                $table->string('cfdi_uuid', 36)->nullable()->after('uso_cfdi');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_version')) {
                $table->string('cfdi_version', 4)->default('4.0')->after('cfdi_uuid');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_xml')) {
                $table->longText('cfdi_xml')->nullable()->after('cfdi_version');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_pdf')) {
                $table->longText('cfdi_pdf')->nullable()->after('cfdi_xml');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_no_certificado')) {
                $table->string('cfdi_no_certificado', 20)->nullable()->after('cfdi_pdf');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_certificado')) {
                $table->longText('cfdi_certificado')->nullable()->after('cfdi_no_certificado');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_sello')) {
                $table->longText('cfdi_sello')->nullable()->after('cfdi_certificado');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_cadena_original')) {
                $table->longText('cfdi_cadena_original')->nullable()->after('cfdi_sello');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_fecha_timbrado')) {
                $table->dateTime('cfdi_fecha_timbrado')->nullable()->after('cfdi_cadena_original');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_fecha_cancelacion')) {
                $table->dateTime('cfdi_fecha_cancelacion')->nullable()->after('cfdi_fecha_timbrado');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_motivo_cancelacion')) {
                $table->string('cfdi_motivo_cancelacion', 2)->nullable()->after('cfdi_fecha_cancelacion');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_folio_sustitucion')) {
                $table->string('cfdi_folio_sustitucion', 36)->nullable()->after('cfdi_motivo_cancelacion');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_estatus_sat')) {
                $table->string('cfdi_estatus_sat', 50)->nullable()->after('cfdi_folio_sustitucion');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_es_cancelable')) {
                $table->string('cfdi_es_cancelable', 50)->nullable()->after('cfdi_estatus_sat');
            }
            if (!Schema::hasColumn('pagos', 'cfdi_estatus_cancelacion')) {
                $table->string('cfdi_estatus_cancelacion', 50)->nullable()->after('cfdi_es_cancelable');
            }
            if (!Schema::hasColumn('pagos', 'estatus_cfdi')) {
                $table->string('estatus_cfdi', 50)->default('borrador')->after('cfdi_estatus_cancelacion');
            }
        });

        // Normalizar valores legados a claves SAT antes de aplicar llaves foraneas
        DB::table('pagos')->where('forma_pago', 'Efectivo')->update(['forma_pago' => '01']);
        DB::table('pagos')->where('forma_pago', 'Cheque')->update(['forma_pago' => '02']);
        DB::table('pagos')->where('forma_pago', 'Transferencia')->update(['forma_pago' => '03']);
        DB::table('pagos')->where('forma_pago', 'Tarjeta Crédito')->update(['forma_pago' => '04']);
        DB::table('pagos')->where('forma_pago', 'Tarjeta Debito')->update(['forma_pago' => '28']);
        DB::table('pagos')->where('forma_pago', 'Tarjeta Débito')->update(['forma_pago' => '28']);
        DB::table('pagos')->where('forma_pago', 'Otro')->update(['forma_pago' => '99']);

        Schema::table('pagos', function (Blueprint $table) {
            $table->foreign('forma_pago')->references('clave')->on('sat_forma_pago')->restrictOnDelete();
            $table->foreign('metodo_pago')->references('clave')->on('sat_metodo_pago')->nullOnDelete();
            $table->foreign('moneda')->references('clave')->on('sat_moneda')->restrictOnDelete();
            $table->foreign('tipo_comprobante')->references('clave')->on('sat_tipo_comprobante')->restrictOnDelete();
            $table->foreign('exportacion')->references('clave')->on('sat_exportacion')->restrictOnDelete();
            $table->foreign('lugar_expedicion')->references('clave')->on('sat_codigo_postal')->nullOnDelete();
            $table->foreign('regimen_fiscal_receptor')->references('clave')->on('sat_regimen_fiscal')->nullOnDelete();
            $table->foreign('domicilio_fiscal_receptor')->references('clave')->on('sat_codigo_postal')->nullOnDelete();
            $table->foreign('uso_cfdi')->references('clave')->on('sat_uso_cfdi')->restrictOnDelete();
            $table->foreign('cfdi_motivo_cancelacion')->references('clave')->on('sat_motivo_cancelacion')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['forma_pago']);
            $table->dropForeign(['metodo_pago']);
            $table->dropForeign(['moneda']);
            $table->dropForeign(['tipo_comprobante']);
            $table->dropForeign(['exportacion']);
            $table->dropForeign(['lugar_expedicion']);
            $table->dropForeign(['regimen_fiscal_receptor']);
            $table->dropForeign(['domicilio_fiscal_receptor']);
            $table->dropForeign(['uso_cfdi']);
            $table->dropForeign(['cfdi_motivo_cancelacion']);
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn([
                'serie',
                'folio',
                'fecha_emision',
                'fecha_pago_hora',
                'moneda',
                'tipo_cambio',
                'tipo_comprobante',
                'exportacion',
                'lugar_expedicion',
                'rfc_emisor',
                'nombre_emisor',
                'rfc_receptor',
                'nombre_receptor',
                'regimen_fiscal_receptor',
                'domicilio_fiscal_receptor',
                'uso_cfdi',
                'cfdi_uuid',
                'cfdi_version',
                'cfdi_xml',
                'cfdi_pdf',
                'cfdi_no_certificado',
                'cfdi_certificado',
                'cfdi_sello',
                'cfdi_cadena_original',
                'cfdi_fecha_timbrado',
                'cfdi_fecha_cancelacion',
                'cfdi_motivo_cancelacion',
                'cfdi_folio_sustitucion',
                'cfdi_estatus_sat',
                'cfdi_es_cancelable',
                'cfdi_estatus_cancelacion',
                'estatus_cfdi',
            ]);
        });
    }
};
