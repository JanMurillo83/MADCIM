<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\Tables;
use App\Services\GenerarNominaSemanalService;
use Filament\Actions\{Action,EditAction};
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class PeriodosNominaTable
{
    public static function configure(Table $table): Table { return $table->columns([
        TextColumn::make('fecha_inicio')->label('Inicio')->date('d/m/Y')->sortable(),
        TextColumn::make('fecha_fin')->label('Fin')->date('d/m/Y')->sortable(),
        TextColumn::make('nominas_count')->counts('nominas')->label('Empleados'),
        TextColumn::make('estatus')->label('Estatus')->badge(),
        TextColumn::make('generada_en')->label('Generada')->dateTime('d/m/Y H:i')->placeholder('Pendiente'),
    ])->recordActions([
        EditAction::make()->visible(fn ($record): bool => $record->estatus === 'abierto'),
        Action::make('generar')->label('Generar nómina')->color('success')->icon('heroicon-o-calculator')->requiresConfirmation()->visible(fn ($record): bool => $record->estatus === 'abierto')->action(function ($record): void { app(GenerarNominaSemanalService::class)->generar($record, auth()->id()); Notification::make()->title('Nómina generada')->success()->send(); }),
    ])->defaultSort('fecha_inicio','desc'); }
}
