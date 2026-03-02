<?php

namespace App\Filament\Resources\ItemsEnRenta;

use App\Filament\Resources\ItemsEnRenta\Pages\ListItemsEnRenta;
use App\Models\RegistroRenta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class ItemsEnRentaResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = RegistroRenta::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-boxes-packing';

    protected static ?string $navigationLabel = 'Productos en Renta';

    protected static ?string $pluralLabel = 'Productos en Renta';
    protected static string|null|\UnitEnum $navigationGroup = 'Consultas';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('notaVentaRenta.folio')
                    ->label('Folio Nota')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_renta')
                    ->label('Fecha Renta')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('producto.descripcion')
                    ->label('Producto')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dias_renta')
                    ->label('Días Renta')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Fecha Vencimiento')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('importe_renta')
                    ->label('Importe Renta')
                    ->money('MXN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('importe_deposito')
                    ->label('Depósito')
                    ->money('MXN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Activo' => 'success',
                        'Devuelto' => 'info',
                        'Vencido' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'Activo' => 'Activo',
                        'Devuelto' => 'Devuelto',
                        'Vencido' => 'Vencido',
                    ]),
            ])
            ->defaultSort('fecha_renta', 'desc')
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
            'index' => ListItemsEnRenta::route('/'),
        ];
    }
}
