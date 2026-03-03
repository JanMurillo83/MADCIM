<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function foreignKeyExists(string $table, string $name): bool
    {
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $name]
        );

        return !empty($rows);
    }

    public function up(): void
    {
        Schema::table('facturas_cfdi', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas_cfdi', 'tipo_comprobante')) {
                $table->string('tipo_comprobante', 1)->default('I')->after('total');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'exportacion')) {
                $table->string('exportacion', 2)->default('01')->after('tipo_comprobante');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'lugar_expedicion')) {
                $table->string('lugar_expedicion', 5)->nullable()->after('exportacion');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'domicilio_fiscal_receptor')) {
                $table->string('domicilio_fiscal_receptor', 5)->nullable()->after('razon_social_receptor');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'regimen_fiscal_emisor')) {
                $table->string('regimen_fiscal_emisor', 5)->nullable()->after('rfc_emisor');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'nombre_emisor')) {
                $table->string('nombre_emisor')->nullable()->after('regimen_fiscal_emisor');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'descuento')) {
                $table->decimal('descuento', 18, 8)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'condiciones_pago')) {
                $table->string('condiciones_pago')->nullable()->after('folio');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_version')) {
                $table->string('cfdi_version', 4)->default('4.0')->after('cfdi_uuid');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_xml')) {
                $table->longText('cfdi_xml')->nullable()->after('cfdi_version');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_pdf')) {
                $table->longText('cfdi_pdf')->nullable()->after('cfdi_xml');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_no_certificado')) {
                $table->string('cfdi_no_certificado', 20)->nullable()->after('cfdi_pdf');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_certificado')) {
                $table->longText('cfdi_certificado')->nullable()->after('cfdi_no_certificado');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_sello')) {
                $table->longText('cfdi_sello')->nullable()->after('cfdi_certificado');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_cadena_original')) {
                $table->longText('cfdi_cadena_original')->nullable()->after('cfdi_sello');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_fecha_timbrado')) {
                $table->dateTime('cfdi_fecha_timbrado')->nullable()->after('cfdi_cadena_original');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_fecha_cancelacion')) {
                $table->dateTime('cfdi_fecha_cancelacion')->nullable()->after('cfdi_fecha_timbrado');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_motivo_cancelacion')) {
                $table->string('cfdi_motivo_cancelacion', 2)->nullable()->after('cfdi_fecha_cancelacion');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_folio_sustitucion')) {
                $table->string('cfdi_folio_sustitucion', 36)->nullable()->after('cfdi_motivo_cancelacion');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_estatus_sat')) {
                $table->string('cfdi_estatus_sat', 50)->nullable()->after('cfdi_folio_sustitucion');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_es_cancelable')) {
                $table->string('cfdi_es_cancelable', 50)->nullable()->after('cfdi_estatus_sat');
            }
            if (!Schema::hasColumn('facturas_cfdi', 'cfdi_estatus_cancelacion')) {
                $table->string('cfdi_estatus_cancelacion', 50)->nullable()->after('cfdi_es_cancelable');
            }
        });

        Schema::table('facturas_cfdi', function (Blueprint $table) {
            if (!$this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_uso_cfdi_foreign')) {
                $table->foreign('uso_cfdi')->references('clave')->on('sat_uso_cfdi')->nullOnDelete();
            }
            if (!$this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_forma_pago_foreign')) {
                $table->foreign('forma_pago')->references('clave')->on('sat_forma_pago')->nullOnDelete();
            }
            if (!$this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_metodo_pago_foreign')) {
                $table->foreign('metodo_pago')->references('clave')->on('sat_metodo_pago')->nullOnDelete();
            }
            if (!$this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_regimen_fiscal_receptor_foreign')) {
                $table->foreign('regimen_fiscal_receptor')->references('clave')->on('sat_regimen_fiscal')->nullOnDelete();
            }
            if (!$this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_regimen_fiscal_emisor_foreign')) {
                $table->foreign('regimen_fiscal_emisor')->references('clave')->on('sat_regimen_fiscal')->nullOnDelete();
            }
            if (!$this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_tipo_comprobante_foreign')) {
                $table->foreign('tipo_comprobante')->references('clave')->on('sat_tipo_comprobante')->restrictOnDelete();
            }
            if (!$this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_exportacion_foreign')) {
                $table->foreign('exportacion')->references('clave')->on('sat_exportacion')->restrictOnDelete();
            }
            if (!$this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_lugar_expedicion_foreign')) {
                $table->foreign('lugar_expedicion')->references('clave')->on('sat_codigo_postal')->nullOnDelete();
            }
            if (!$this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_domicilio_fiscal_receptor_foreign')) {
                $table->foreign('domicilio_fiscal_receptor')->references('clave')->on('sat_codigo_postal')->nullOnDelete();
            }
            if (!$this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_cfdi_motivo_cancelacion_foreign')) {
                $table->foreign('cfdi_motivo_cancelacion')->references('clave')->on('sat_motivo_cancelacion')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('facturas_cfdi', function (Blueprint $table) {
            if ($this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_uso_cfdi_foreign')) {
                $table->dropForeign('facturas_cfdi_uso_cfdi_foreign');
            }
            if ($this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_forma_pago_foreign')) {
                $table->dropForeign('facturas_cfdi_forma_pago_foreign');
            }
            if ($this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_metodo_pago_foreign')) {
                $table->dropForeign('facturas_cfdi_metodo_pago_foreign');
            }
            if ($this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_regimen_fiscal_receptor_foreign')) {
                $table->dropForeign('facturas_cfdi_regimen_fiscal_receptor_foreign');
            }
            if ($this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_regimen_fiscal_emisor_foreign')) {
                $table->dropForeign('facturas_cfdi_regimen_fiscal_emisor_foreign');
            }
            if ($this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_tipo_comprobante_foreign')) {
                $table->dropForeign('facturas_cfdi_tipo_comprobante_foreign');
            }
            if ($this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_exportacion_foreign')) {
                $table->dropForeign('facturas_cfdi_exportacion_foreign');
            }
            if ($this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_lugar_expedicion_foreign')) {
                $table->dropForeign('facturas_cfdi_lugar_expedicion_foreign');
            }
            if ($this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_domicilio_fiscal_receptor_foreign')) {
                $table->dropForeign('facturas_cfdi_domicilio_fiscal_receptor_foreign');
            }
            if ($this->foreignKeyExists('facturas_cfdi', 'facturas_cfdi_cfdi_motivo_cancelacion_foreign')) {
                $table->dropForeign('facturas_cfdi_cfdi_motivo_cancelacion_foreign');
            }
        });

        Schema::table('facturas_cfdi', function (Blueprint $table) {
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
};
