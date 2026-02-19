<?php

namespace App\Filament\Resources\NotasRentadas;

use App\Filament\Resources\NotasRentadas\Pages\ListNotasRentadas;
use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\DevolucionesRenta;
use App\Models\DevolucionRentaPartidas;
use App\Models\NotasVentaRenta;
use App\Models\NotaVentaRentaPartidas;
use App\Models\RegistroRenta;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NotasRentadasResource extends Resource
{
    protected static ?string $model = NotasVentaRenta::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-file-contract';

    protected static ?string $navigationLabel = 'Notas Rentadas';

    protected static ?string $pluralLabel = 'Notas Rentadas';

    protected static string|null|\UnitEnum $navigationGroup = 'Consultas';

    public static function table(Table $table): Table
    {
        return $table
            ->query(NotasVentaRenta::query()->whereIn('estatus', ['Activa','Pagada','Devuelta']))
            ->columns([
                Tables\Columns\TextColumn::make('folio')
                    ->label('Folio')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_emision')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('direccionEntrega.nombre_direccion')
                    ->label('Dirección de Entrega')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge()
                    ->colors([
                        'success' => 'Activa',
                        'info' => 'Pagada',
                        'warning' => 'Devuelta',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Fecha Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->getStateUsing(function ($record){
                        if($record->fecha_vencimiento == null || $record->fecha_vencimiento == '')
                            return $record->fecha_emision->addDays(30);
                        else
                            return $record->fecha_vencimiento;
                    }),
            ])
            ->recordActions([
                Action::make('ver_detalle')
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (NotasVentaRenta $record) => 'Detalle de Items en Renta - Folio ' . $record->folio)
                    ->modalWidth('7xl')
                    ->modalContent(function (NotasVentaRenta $record) {
                        $items = RegistroRenta::where('nota_venta_renta_id', $record->id)
                            ->with('producto')
                            ->get();

                        return view('filament.resources.notas-rentadas.detalle-items', [
                            'items' => $items,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Action::make('devolver')
                    ->label('Devolver')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (NotasVentaRenta $record) => $record->estatus !== 'Devuelta')
                    ->modalHeading(fn (NotasVentaRenta $record) => 'Devolución de Renta - Folio ' . $record->folio)
                    ->modalDescription(fn (NotasVentaRenta $record) => 'Depósito registrado: $' . number_format((float)$record->deposito, 2))
                    ->modalWidth('7xl')
                    ->modalSubmitActionLabel('Procesar Devolución')
                    ->form(function (NotasVentaRenta $record): array {
                        $items = RegistroRenta::where('nota_venta_renta_id', $record->id)
                            ->with('producto')
                            ->get();

                        $fields = [];

                        $fields[] = Section::make('Material a devolver')
                            ->description('Ingrese la cantidad devuelta por cada item. Si hay faltantes se descontarán del depósito a precio de venta.')
                            ->schema(
                                $items->flatMap(function ($item) {
                                    $precioVenta = $item->producto ? (float)$item->producto->precio_venta : 0;
                                    return [
                                        Placeholder::make('desc_' . $item->id)
                                            ->label($item->producto ? $item->producto->descripcion : ($item->observaciones ?? 'Item'))
                                            ->content('Cantidad rentada: ' . $item->cantidad . ' | Precio venta unitario: $' . number_format($precioVenta, 2))
                                            ->columnSpan(2),
                                        Hidden::make('item_id_' . $item->id)
                                            ->default($item->id),
                                        Hidden::make('cantidad_original_' . $item->id)
                                            ->default($item->cantidad),
                                        Hidden::make('precio_venta_' . $item->id)
                                            ->default($precioVenta),
                                        TextInput::make('cantidad_devuelta_' . $item->id)
                                            ->label('Cantidad devuelta')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue($item->cantidad)
                                            ->default($item->cantidad)
                                            ->required()
                                            ->columnSpan(1),
                                    ];
                                })->toArray()
                            )->columns(3);

                        $fields[] = Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3);

                        return $fields;
                    })
                    ->action(function (NotasVentaRenta $record, array $data) {
                        $items = RegistroRenta::where('nota_venta_renta_id', $record->id)
                            ->with('producto')
                            ->get();

                        $totalDescuento = 0;
                        $detallesFaltantes = [];

                        foreach ($items as $item) {
                            $cantidadOriginal = (float)$item->cantidad;
                            $cantidadDevuelta = (float)($data['cantidad_devuelta_' . $item->id] ?? $cantidadOriginal);
                            $faltante = $cantidadOriginal - $cantidadDevuelta;

                            if ($faltante > 0) {
                                $precioVenta = $item->producto ? (float)$item->producto->precio_venta : 0;
                                $descuento = $faltante * $precioVenta;
                                $totalDescuento += $descuento;
                                $detallesFaltantes[] = [
                                    'producto' => $item->producto ? $item->producto->descripcion : ($item->observaciones ?? 'Item'),
                                    'faltante' => $faltante,
                                    'precio_unitario' => $precioVenta,
                                    'descuento' => $descuento,
                                ];
                            }

                            // Actualizar estado del registro de renta
                            $item->update(['estado' => 'Devuelto']);
                        }

                        $deposito = (float)$record->deposito;
                        $importeDevolver = max(0, $deposito - $totalDescuento);

                        // Crear registro de devolución
                        $devolucion = DevolucionesRenta::create([
                            'serie' => 'DR',
                            'folio' => (DevolucionesRenta::max('folio') ?? 0) + 1,
                            'fecha_emision' => now(),
                            'moneda' => $record->moneda ?? 'MXN',
                            'tipo_cambio' => $record->tipo_cambio ?? 1,
                            'subtotal' => $totalDescuento,
                            'impuestos_total' => 0,
                            'total' => $totalDescuento,
                            'estatus' => 'Aplicada',
                            'documento_origen_id' => $record->id,
                        ]);

                        // Crear partidas de la devolución (faltantes)
                        foreach ($detallesFaltantes as $detalle) {
                            DevolucionRentaPartidas::create([
                                'devolucion_renta_id' => $devolucion->id,
                                'cantidad' => $detalle['faltante'],
                                'item' => $detalle['producto'],
                                'descripcion' => 'Faltante - ' . $detalle['producto'],
                                'valor_unitario' => $detalle['precio_unitario'],
                                'subtotal' => $detalle['descuento'],
                                'impuestos' => 0,
                                'total' => $detalle['descuento'],
                            ]);
                        }

                        // Registrar egreso en caja si hay importe a devolver
                        if ($importeDevolver > 0) {
                            $cajaAbierta = Caja::where('estatus', 'Abierta')->first();

                            if ($cajaAbierta) {
                                CajaMovimiento::create([
                                    'caja_id' => $cajaAbierta->id,
                                    'tipo' => 'Egreso',
                                    'fuente' => 'Devolución depósito renta',
                                    'metodo_pago' => 'Efectivo',
                                    'importe' => $importeDevolver,
                                    'referencia' => 'Devolución Folio ' . $record->serie . '-' . $record->folio,
                                    'observaciones' => $data['observaciones'] ?? null,
                                    'user_id' => Auth::id(),
                                    'fecha' => now(),
                                    'movimentable_type' => DevolucionesRenta::class,
                                    'movimentable_id' => $devolucion->id,
                                ]);

                                // Actualizar totales de caja
                                $eg = $cajaAbierta->movimientos()->where('tipo', 'Egreso')->where('metodo_pago', 'Efectivo')->sum('importe');
                                $cajaAbierta->update(['total_egresos_cash' => $eg]);
                            }
                        }

                        // Actualizar estatus de la nota
                        $record->update(['estatus' => 'Devuelta']);

                        $mensaje = 'Devolución procesada. ';
                        if ($totalDescuento > 0) {
                            $mensaje .= 'Descuento por faltantes: $' . number_format($totalDescuento, 2) . '. ';
                        }
                        $mensaje .= 'Depósito a devolver: $' . number_format($importeDevolver, 2);

                        if ($importeDevolver > 0 && !Caja::where('estatus', 'Abierta')->exists()) {
                            $mensaje .= ' (⚠ No se encontró caja abierta para registrar el egreso)';
                        }

                        Notification::make()
                            ->title('Devolución procesada')
                            ->body($mensaje)
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('renovar')
                    ->label('Renovar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (NotasVentaRenta $record) => $record->estatus !== 'Devuelta')
                    ->requiresConfirmation()
                    ->modalHeading(fn (NotasVentaRenta $record) => 'Renovar Renta - Folio ' . $record->folio)
                    ->modalDescription(fn (NotasVentaRenta $record) => 'Se marcará la nota actual como Devuelta y se generará una nueva nota con los mismos datos. Depósito: $' . number_format((float)$record->deposito, 2))
                    ->modalSubmitActionLabel('Renovar Renta')
                    ->action(function (NotasVentaRenta $record) {
                        $deposito = (float)$record->deposito;

                        // 1. Marcar registros de renta originales como Devueltos
                        RegistroRenta::where('nota_venta_renta_id', $record->id)
                            ->update(['estado' => 'Devuelto']);

                        // 2. Marcar nota original como Devuelta
                        $record->update(['estatus' => 'Devuelta']);

                        // 3. Crear nueva nota con los mismos datos
                        $nuevoFolio = (NotasVentaRenta::where('serie', $record->serie)->selectRaw('MAX(CAST(folio AS UNSIGNED)) as max_folio')->value('max_folio') ?? 0) + 1;

                        $nuevaNota = NotasVentaRenta::create([
                            'cliente_id' => $record->cliente_id,
                            'direccion_entrega_id' => $record->direccion_entrega_id,
                            'serie' => $record->serie,
                            'folio' => $nuevoFolio,
                            'fecha_emision' => now(),
                            'dias_renta' => $record->dias_renta,
                            'fecha_vencimiento' => now()->addDays($record->dias_renta ?? 30),
                            'moneda' => $record->moneda ?? 'MXN',
                            'tipo_cambio' => $record->tipo_cambio ?? 1,
                            'deposito' => $record->deposito,
                            'subtotal' => $record->subtotal,
                            'impuestos_total' => $record->impuestos_total,
                            'total' => $record->total,
                            'saldo_pendiente' => $record->saldo_pendiente,
                            'estatus' => 'Activa',
                            'uso_cfdi' => $record->uso_cfdi,
                            'forma_pago' => $record->forma_pago,
                            'metodo_pago' => $record->metodo_pago,
                            'regimen_fiscal_receptor' => $record->regimen_fiscal_receptor,
                            'rfc_emisor' => $record->rfc_emisor,
                            'rfc_receptor' => $record->rfc_receptor,
                            'razon_social_receptor' => $record->razon_social_receptor,
                            'documento_origen_id' => $record->id,
                        ]);

                        // 4. Copiar partidas de la nota original
                        foreach ($record->partidas as $partida) {
                            NotaVentaRentaPartidas::create([
                                'nota_venta_renta_id' => $nuevaNota->id,
                                'cantidad' => $partida->cantidad,
                                'item' => $partida->item,
                                'descripcion' => $partida->descripcion,
                                'valor_unitario' => $partida->valor_unitario,
                                'subtotal' => $partida->subtotal,
                                'impuestos' => $partida->impuestos,
                                'total' => $partida->total,
                            ]);
                        }

                        // 5. Copiar registros de renta a la nueva nota
                        $registrosOriginales = RegistroRenta::where('nota_venta_renta_id', $record->id)->get();
                        foreach ($registrosOriginales as $registro) {
                            RegistroRenta::create([
                                'nota_venta_renta_id' => $nuevaNota->id,
                                'cliente_id' => $registro->cliente_id ?? $record->cliente_id,
                                'cliente_nombre' => $registro->cliente_nombre,
                                'cliente_contacto' => $registro->cliente_contacto,
                                'cliente_telefono' => $registro->cliente_telefono,
                                'cliente_direccion' => $registro->cliente_direccion,
                                'producto_id' => $registro->producto_id,
                                'cantidad' => $registro->cantidad,
                                'dias_renta' => $registro->dias_renta,
                                'fecha_renta' => now(),
                                'fecha_vencimiento' => now()->addDays($registro->dias_renta ?? 30),
                                'importe_renta' => $registro->importe_renta,
                                'importe_deposito' => $registro->importe_deposito,
                                'observaciones' => $registro->observaciones,
                                'estado' => 'Activo',
                            ]);
                        }

                        // 6. Registrar movimientos en caja (egreso + ingreso = efecto 0)
                        $cajaAbierta = Caja::where('estatus', 'Abierta')->first();
                        $cajaMsg = '';

                        if ($cajaAbierta && $deposito > 0) {
                            // Egreso: devolución del depósito original
                            CajaMovimiento::create([
                                'caja_id' => $cajaAbierta->id,
                                'tipo' => 'Egreso',
                                'fuente' => 'Devolución depósito renta (renovación)',
                                'metodo_pago' => 'Efectivo',
                                'importe' => $deposito,
                                'referencia' => 'Renovación Folio ' . $record->serie . '-' . $record->folio,
                                'observaciones' => 'Egreso por renovación de renta',
                                'user_id' => Auth::id(),
                                'fecha' => now(),
                                'movimentable_type' => NotasVentaRenta::class,
                                'movimentable_id' => $record->id,
                            ]);

                            // Ingreso: depósito de la nueva nota
                            CajaMovimiento::create([
                                'caja_id' => $cajaAbierta->id,
                                'tipo' => 'Ingreso',
                                'fuente' => 'Depósito renta (renovación)',
                                'metodo_pago' => 'Efectivo',
                                'importe' => $deposito,
                                'referencia' => 'Renovación Folio ' . $nuevaNota->serie . '-' . $nuevaNota->folio,
                                'observaciones' => 'Ingreso por renovación de renta',
                                'user_id' => Auth::id(),
                                'fecha' => now(),
                                'movimentable_type' => NotasVentaRenta::class,
                                'movimentable_id' => $nuevaNota->id,
                            ]);

                            // Actualizar totales de caja
                            $eg = $cajaAbierta->movimientos()->where('tipo', 'Egreso')->where('metodo_pago', 'Efectivo')->sum('importe');
                            $ig = $cajaAbierta->movimientos()->where('tipo', 'Ingreso')->where('metodo_pago', 'Efectivo')->sum('importe');
                            $cajaAbierta->update([
                                'total_egresos_cash' => $eg,
                                'total_ingresos_cash' => $ig,
                            ]);
                        } elseif ($deposito > 0) {
                            $cajaMsg = ' (⚠ No se encontró caja abierta para registrar los movimientos)';
                        }

                        Notification::make()
                            ->title('Renta renovada exitosamente')
                            ->body('Nueva nota generada: ' . $nuevaNota->serie . '-' . $nuevaNota->folio . '. Depósito: $' . number_format($deposito, 2) . ' (efecto 0 en caja)' . $cajaMsg)
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('hoja_embarque')
                    ->visible(false)
                    ->label('Hoja de Embarque')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->url(fn (NotasVentaRenta $record) => route('notas-venta-renta.hoja-embarque', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('fecha_emision', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotasRentadas::route('/'),
        ];
    }
}
