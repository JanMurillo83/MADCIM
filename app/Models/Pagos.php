<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Pagos extends Model
{
    protected $fillable = [
        'serie',
        'folio',
        'fecha_emision',
        'documento_tipo',
        'documento_id',
        'cliente_id',
        'fecha_pago',
        'fecha_pago_hora',
        'forma_pago',
        'metodo_pago',
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
        'importe',
        'referencia',
        'user_id',
        'caja_id',
        'observaciones',
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
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'fecha_emision' => 'datetime',
        'fecha_pago_hora' => 'datetime',
        'cfdi_fecha_timbrado' => 'datetime',
        'cfdi_fecha_cancelacion' => 'datetime',
        'importe' => 'decimal:2',
    ];

    // Boot method para actualizar saldo_pendiente al guardar
    protected static function booted()
    {
        static::created(function ($pago) {
            $pago->actualizarSaldoPendiente();
            $pago->sincronizarMovimientoCaja();
        });

        static::updated(function ($pago) {
            $pago->actualizarSaldoPendiente();
            $pago->sincronizarMovimientoCaja();
        });

        static::deleted(function ($pago) {
            $pago->actualizarSaldoPendiente();
            // Eliminar movimiento de caja asociado si existe
            try {
                \App\Models\CajaMovimiento::where('movimentable_type', self::class)
                    ->where('movimentable_id', $pago->id)
                    ->delete();
            } catch (\Throwable $e) {
                // swallow
            }
        });
    }

    public function actualizarSaldoPendiente()
    {
        $documento = null;

        switch ($this->documento_tipo) {
            case 'notas_venta_renta':
                $documento = NotasVentaRenta::find($this->documento_id);
                break;
            case 'notas_venta_venta':
                $documento = NotasVentaVenta::find($this->documento_id);
                break;
            case 'facturas_cfdi':
                $documento = FacturasCfdi::find($this->documento_id);
                break;
        }

        if ($documento) {
            // Calcular total de pagos para este documento
            $totalPagos = static::where('documento_tipo', $this->documento_tipo)
                ->where('documento_id', $this->documento_id)
                ->sum('importe');

            // Actualizar saldo pendiente
            $documento->saldo_pendiente = $documento->total - $totalPagos;

            // Cambiar estatus a Pagada si saldo es 0
            if ($documento->saldo_pendiente <= 0) {
                $documento->estatus = 'Pagada';
            } else {
                // Si tiene saldo pendiente y no está Cancelada, ponerla como Activa
                if ($documento->estatus !== 'Cancelada') {
                    $documento->estatus = 'Activa';
                }
            }

            $documento->save();
        }
    }

    public function sincronizarMovimientoCaja(): void
    {
        // Solo aplicable a pagos en efectivo y con caja asignada
        if (($this->metodo_pago ?? $this->forma_pago) !== 'Efectivo' || empty($this->caja_id)) {
            // Si existe un movimiento previo, eliminarlo
            try {
                \App\Models\CajaMovimiento::where('movimentable_type', self::class)
                    ->where('movimentable_id', $this->id)
                    ->delete();
            } catch (\Throwable $e) {}
            return;
        }

        // Upsert del movimiento de caja asociado a este pago
        \App\Models\CajaMovimiento::updateOrCreate(
            [
                'movimentable_type' => self::class,
                'movimentable_id' => $this->id,
            ],
            [
                'caja_id' => $this->caja_id,
                'tipo' => 'Ingreso',
                'fuente' => 'pago',
                'metodo_pago' => 'Efectivo',
                'importe' => $this->importe,
                'referencia' => $this->referencia,
                'observaciones' => $this->observaciones,
                'user_id' => $this->user_id,
                'fecha' => $this->fecha_pago ?? now(),
            ]
        );
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Caja::class, 'caja_id');
    }

    public function documento(): MorphTo
    {
        return $this->morphTo();
    }

    public function cfdiDoctos()
    {
        return $this->hasMany(CfdiPagoDocto::class, 'pago_id');
    }
}
