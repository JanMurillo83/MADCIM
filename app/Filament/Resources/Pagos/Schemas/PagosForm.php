<?php

namespace App\Filament\Resources\Pagos\Schemas;

use App\Models\Clientes;
use App\Models\FacturasCfdi;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PagosForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Pago')
                    ->schema([
                        Select::make('documento_tipo')
                            ->label('Tipo de documento')
                            ->required()
                            ->options([
                                'notas_venta_renta' => 'Nota de Venta (Renta)',
                                'notas_venta_venta' => 'Nota de Venta (Venta)',
                                'facturas_cfdi' => 'Factura CFDI',
                            ])
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('documento_id', null);
                                $set('cliente_id', null);
                            }),
                        Select::make('documento_id')
                            ->label('Documento')
                            ->required()
                            ->searchable()
                            ->options(function (Get $get) {
                                $tipo = $get('documento_tipo');
                                if (!$tipo) {
                                    return [];
                                }

                                switch ($tipo) {
                                    case 'notas_venta_renta':
                                        return NotasVentaRenta::where('estatus', '!=', 'Cancelada')
                                            ->where('saldo_pendiente', '>', 0)
                                            ->get()
                                            ->mapWithKeys(function ($nota) {
                                                return [$nota->id => $nota->folio . ' - $' . number_format($nota->saldo_pendiente, 2)];
                                            });
                                    case 'notas_venta_venta':
                                        return NotasVentaVenta::where('estatus', '!=', 'Cancelada')
                                            ->where('saldo_pendiente', '>', 0)
                                            ->get()
                                            ->mapWithKeys(function ($nota) {
                                                return [$nota->id => $nota->folio . ' - $' . number_format($nota->saldo_pendiente, 2)];
                                            });
                                    case 'facturas_cfdi':
                                        return FacturasCfdi::where('estatus', '!=', 'Cancelada')
                                            ->where('saldo_pendiente', '>', 0)
                                            ->get()
                                            ->mapWithKeys(function ($factura) {
                                                return [$factura->id => $factura->folio . ' - $' . number_format($factura->saldo_pendiente, 2)];
                                            });
                                    default:
                                        return [];
                                }
                            })
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $tipo = $get('documento_tipo');
                                $documentoId = $get('documento_id');

                                if (!$tipo || !$documentoId) {
                                    return;
                                }

                                $documento = null;
                                switch ($tipo) {
                                    case 'notas_venta_renta':
                                        $documento = NotasVentaRenta::find($documentoId);
                                        break;
                                    case 'notas_venta_venta':
                                        $documento = NotasVentaVenta::find($documentoId);
                                        break;
                                    case 'facturas_cfdi':
                                        $documento = FacturasCfdi::find($documentoId);
                                        break;
                                }

                                if ($documento) {
                                    $set('cliente_id', $documento->cliente_id);
                                }
                            }),
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => $get('documento_id') !== null)
                            ->dehydrated(),
                        DatePicker::make('fecha_pago')
                            ->label('Fecha de pago')
                            ->required()
                            ->default(Carbon::now()->format('Y-m-d'))
                            ->format('Y-m-d'),
                        Select::make('forma_pago')
                            ->label('Forma de pago')
                            ->required()
                            ->options([
                                'Efectivo' => 'Efectivo',
                                'Tarjeta Débito' => 'Tarjeta Débito',
                                'Tarjeta Crédito' => 'Tarjeta Crédito',
                                'Transferencia' => 'Transferencia',
                                'Cheque' => 'Cheque',
                                'Otro' => 'Otro',
                            ])
                            ->default('Efectivo'),
                        TextInput::make('importe')
                            ->label('Importe')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0.01),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }
}
