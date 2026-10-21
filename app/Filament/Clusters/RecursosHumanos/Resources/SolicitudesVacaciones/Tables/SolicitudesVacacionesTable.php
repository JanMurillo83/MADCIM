<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\SolicitudesVacaciones\Tables;
use App\Enums\EstadoVacacion;
use App\Services\GestionarVacacionService;
use Filament\Actions\{Action,EditAction};
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class SolicitudesVacacionesTable
{
    public static function configure(Table $table): Table { return $table->columns([
        TextColumn::make('empleado.nombre_completo')->label('Empleado'),
        TextColumn::make('fecha_inicio')->label('Inicio')->date('d/m/Y'),
        TextColumn::make('fecha_fin')->label('Fin')->date('d/m/Y'),
        TextColumn::make('dias')->label('Días'),
        TextColumn::make('estatus')->label('Estatus')->formatStateUsing(fn ($state): string => $state instanceof EstadoVacacion ? ucfirst($state->value) : (string) $state)->badge(),
    ])->recordActions([
        EditAction::make()->visible(fn ($record): bool => $record->estatus === EstadoVacacion::Pendiente),
        Action::make('aprobar')->label('Aprobar')->color('success')->requiresConfirmation()->visible(fn ($record): bool => $record->estatus === EstadoVacacion::Pendiente)->action(function ($record): void { app(GestionarVacacionService::class)->aprobar($record, auth()->id()); Notification::make()->title('Vacaciones aprobadas')->success()->send(); }),
        Action::make('rechazar')->label('Rechazar')->color('danger')->form([Textarea::make('comentario')->required()])->visible(fn ($record): bool => $record->estatus === EstadoVacacion::Pendiente)->action(function ($record, array $data): void { app(GestionarVacacionService::class)->rechazar($record, auth()->id(), $data['comentario']); Notification::make()->title('Solicitud rechazada')->success()->send(); }),
    ])->defaultSort('fecha_inicio','desc'); }
}
