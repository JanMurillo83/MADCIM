<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados\Tables;

use App\Models\Empleado;
use App\Services\GestionarBajaEmpleadoService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BajasEmpleadosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('empleado.nombre_completo')->label('Empleado'),
                TextColumn::make('fecha_baja')->label('Fecha de baja')->date('d/m/Y')->sortable(),
                TextColumn::make('tipo')->label('Tipo')->badge(),
                TextColumn::make('motivo')->limit(50),
                TextColumn::make('empleado.estatus')->label('Estatus actual')->badge()
                    ->color(fn (string $state): string => $state === 'activo' ? 'success' : 'danger'),
            ])
            ->recordActions([
                Action::make('reingresar')
                    ->label('Reingresar')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->form([
                        DatePicker::make('fecha')
                            ->label('Fecha de reingreso')
                            ->default(now()->toDateString())
                            ->required(),
                        Textarea::make('motivo')
                            ->label('Motivo')
                            ->required(),
                    ])
                    ->visible(fn ($record): bool => $record->empleado?->estatus === 'baja')
                    ->requiresConfirmation()
                    ->action(function ($record, array $data): void {
                        app(GestionarBajaEmpleadoService::class)->reingresar(
                            $record->empleado,
                            $data['fecha'],
                            $data['motivo'],
                        );
                        Notification::make()->title('Empleado reingresado')->success()->send();
                    }),
            ])
            ->toolbarActions([
                CreateAction::make()->label('Agregar baja'),
            ]);
    }
}
