<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\Tables;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoIncidencia;
use App\Services\GestionarIncidenciaService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IncidenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('empleado.nombre_completo')
                    ->label('Empleado')
                    ->state(fn ($record): string => $record->empleado?->nombre_completo ?? 'Sin empleado'),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state): string => $state instanceof TipoIncidencia ? $state->label() : (string) $state)
                    ->badge(),
                TextColumn::make('fecha_inicio')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal'),
                TextColumn::make('estatus')
                    ->label('Estatus')
                    ->formatStateUsing(fn ($state): string => $state instanceof EstadoIncidencia ? $state->label() : (string) $state)
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof EstadoIncidencia ? $state->value : $state) {
                        'aprobada' => 'success',
                        'rechazada', 'cancelada' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('minutos')
                    ->label('Min. retardo')
                    ->state(fn ($record): int => in_array($record->tipo, [
                        TipoIncidencia::RetardoAutorizado,
                        TipoIncidencia::ComidaExcedida,
                    ], true) ? (int) ($record->minutos ?? 0) : 0)
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record): bool => $record->estatus === EstadoIncidencia::Pendiente),
                Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record): string => $record->tipo instanceof TipoIncidencia
                        && in_array($record->tipo, [TipoIncidencia::RetardoAutorizado, TipoIncidencia::ComidaExcedida], true)
                            ? "Aprobar {$record->tipo->label()} ({$record->minutos} minutos)"
                            : 'Aprobar incidencia')
                    ->modalDescription(fn ($record): ?string => $record->tipo instanceof TipoIncidencia
                        && in_array($record->tipo, [TipoIncidencia::RetardoAutorizado, TipoIncidencia::ComidaExcedida], true)
                            ? 'Define si los minutos deben descontarse en la nómina.'
                            : null)
                    ->form(fn ($record): array => $record->tipo instanceof TipoIncidencia
                        && in_array($record->tipo, [TipoIncidencia::RetardoAutorizado, TipoIncidencia::ComidaExcedida], true)
                            ? [
                                Toggle::make('afecta_nomina')
                                    ->label('Descontar minutos en nómina')
                                    ->default((bool) $record->afecta_nomina)
                                    ->helperText('Desactivado: se autoriza sin descuento.'),
                            ]
                            : [])
                    ->visible(fn ($record): bool => $record->estatus === EstadoIncidencia::Pendiente)
                    ->action(function ($record, array $data): void {
                        app(GestionarIncidenciaService::class)->aprobar(
                            $record,
                            auth()->id(),
                            null,
                            array_key_exists('afecta_nomina', $data) ? (bool) $data['afecta_nomina'] : null,
                        );
                        Notification::make()->title('Incidencia aprobada')->success()->send();
                    }),
                Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Textarea::make('comentario')
                            ->label('Motivo del rechazo')
                            ->required(),
                    ])
                    ->visible(fn ($record): bool => $record->estatus === EstadoIncidencia::Pendiente)
                    ->action(function ($record, array $data): void {
                        app(GestionarIncidenciaService::class)->rechazar($record, auth()->id(), $data['comentario']);
                        Notification::make()->title('Incidencia rechazada')->success()->send();
                    }),
            ])
            ->toolbarActions([
                CreateAction::make()->label('Agregar incidencia'),
            ])
            ->defaultSort('fecha_inicio', 'desc');
    }
}
