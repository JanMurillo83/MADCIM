<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['devoluciones_renta', 'devoluciones_venta'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'tipo_comprobante')) {
                    $table->string('tipo_comprobante', 1)->default('E')->after('total');
                }
                if (!Schema::hasColumn($tableName, 'exportacion')) {
                    $table->string('exportacion', 2)->default('01')->after('tipo_comprobante');
                }
                if (!Schema::hasColumn($tableName, 'lugar_expedicion')) {
                    $table->string('lugar_expedicion', 5)->nullable()->after('exportacion');
                }
                if (!Schema::hasColumn($tableName, 'domicilio_fiscal_receptor')) {
                    $table->string('domicilio_fiscal_receptor', 5)->nullable()->after('razon_social_receptor');
                }
                if (!Schema::hasColumn($tableName, 'regimen_fiscal_emisor')) {
                    $table->string('regimen_fiscal_emisor', 5)->nullable()->after('rfc_emisor');
                }
                if (!Schema::hasColumn($tableName, 'nombre_emisor')) {
                    $table->string('nombre_emisor')->nullable()->after('regimen_fiscal_emisor');
                }
                if (!Schema::hasColumn($tableName, 'descuento')) {
                    $table->decimal('descuento', 18, 8)->default(0)->after('subtotal');
                }
                if (!Schema::hasColumn($tableName, 'condiciones_pago')) {
                    $table->string('condiciones_pago')->nullable()->after('folio');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_version')) {
                    $table->string('cfdi_version', 4)->default('4.0')->after('cfdi_uuid');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_xml')) {
                    $table->longText('cfdi_xml')->nullable()->after('cfdi_version');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_pdf')) {
                    $table->longText('cfdi_pdf')->nullable()->after('cfdi_xml');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_no_certificado')) {
                    $table->string('cfdi_no_certificado', 20)->nullable()->after('cfdi_pdf');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_certificado')) {
                    $table->longText('cfdi_certificado')->nullable()->after('cfdi_no_certificado');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_sello')) {
                    $table->longText('cfdi_sello')->nullable()->after('cfdi_certificado');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_cadena_original')) {
                    $table->longText('cfdi_cadena_original')->nullable()->after('cfdi_sello');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_fecha_timbrado')) {
                    $table->dateTime('cfdi_fecha_timbrado')->nullable()->after('cfdi_cadena_original');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_fecha_cancelacion')) {
                    $table->dateTime('cfdi_fecha_cancelacion')->nullable()->after('cfdi_fecha_timbrado');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_motivo_cancelacion')) {
                    $table->string('cfdi_motivo_cancelacion', 2)->nullable()->after('cfdi_fecha_cancelacion');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_folio_sustitucion')) {
                    $table->string('cfdi_folio_sustitucion', 36)->nullable()->after('cfdi_motivo_cancelacion');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_estatus_sat')) {
                    $table->string('cfdi_estatus_sat', 50)->nullable()->after('cfdi_folio_sustitucion');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_es_cancelable')) {
                    $table->string('cfdi_es_cancelable', 50)->nullable()->after('cfdi_estatus_sat');
                }
                if (!Schema::hasColumn($tableName, 'cfdi_estatus_cancelacion')) {
                    $table->string('cfdi_estatus_cancelacion', 50)->nullable()->after('cfdi_es_cancelable');
                }
            });
        }

        foreach (['devoluciones_renta', 'devoluciones_venta'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('uso_cfdi')->references('clave')->on('sat_uso_cfdi')->nullOnDelete();
                $table->foreign('forma_pago')->references('clave')->on('sat_forma_pago')->nullOnDelete();
                $table->foreign('metodo_pago')->references('clave')->on('sat_metodo_pago')->nullOnDelete();
                $table->foreign('regimen_fiscal_receptor')->references('clave')->on('sat_regimen_fiscal')->nullOnDelete();
                $table->foreign('regimen_fiscal_emisor')->references('clave')->on('sat_regimen_fiscal')->nullOnDelete();
                $table->foreign('tipo_comprobante')->references('clave')->on('sat_tipo_comprobante')->restrictOnDelete();
                $table->foreign('exportacion')->references('clave')->on('sat_exportacion')->restrictOnDelete();
                $table->foreign('lugar_expedicion')->references('clave')->on('sat_codigo_postal')->nullOnDelete();
                $table->foreign('domicilio_fiscal_receptor')->references('clave')->on('sat_codigo_postal')->nullOnDelete();
                $table->foreign('cfdi_motivo_cancelacion')->references('clave')->on('sat_motivo_cancelacion')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['devoluciones_renta', 'devoluciones_venta'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['uso_cfdi']);
                $table->dropForeign(['forma_pago']);
                $table->dropForeign(['metodo_pago']);
                $table->dropForeign(['regimen_fiscal_receptor']);
                $table->dropForeign(['regimen_fiscal_emisor']);
                $table->dropForeign(['tipo_comprobante']);
                $table->dropForeign(['exportacion']);
                $table->dropForeign(['lugar_expedicion']);
                $table->dropForeign(['domicilio_fiscal_receptor']);
                $table->dropForeign(['cfdi_motivo_cancelacion']);
            });
        }

        foreach (['devoluciones_renta', 'devoluciones_venta'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'tipo_comprobante',
                    'exportacion',
                    'lugar_expedicion',
                    'domicilio_fiscal_receptor',
                    'regimen_fiscal_emisor',
                    'nombre_emisor',
                    'descuento',
                    'condiciones_pago',
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
                ]);
            });
        }
    }
};
