<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\Tables;
use App\Services\GenerarNominaSemanalService;
use App\Services\GestionarPeriodoNominaService;
use App\Models\NominaSemanal;
use App\Services\ExportarNominaOperativaService;
use Filament\Actions\{Action,CreateAction,EditAction};
use Filament\Forms\Components\Select;
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
        Action::make('detalle')
            ->label('Ver detalle')
            ->icon('heroicon-o-eye')
            ->modalHeading(fn ($record): string => "Detalle de nómina {$record->fecha_inicio->format('d/m/Y')} - {$record->fecha_fin->format('d/m/Y')}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn ($record) => view('filament.clusters.recursos-humanos.resources.periodos-nomina.pages.detalle-periodo-nomina-modal', [
                'nominas' => NominaSemanal::query()->with('empleado')->where('periodo_nomina_id', $record->id)->orderBy('empleado_id')->get(),
            ])),
        Action::make('csv')
            ->label('CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->visible(fn ($record): bool => $record->estatus !== 'abierto')
            ->action(fn ($record) => app(ExportarNominaOperativaService::class)->csv($record)),
        Action::make('pdf')
            ->label('PDF')
            ->color('danger')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn ($record): bool => $record->estatus !== 'abierto')
            ->action(fn ($record) => app(ExportarNominaOperativaService::class)->pdf($record)),
        Action::make('recibo')
            ->label('Recibo')
            ->icon('heroicon-o-document-text')
            ->visible(fn ($record): bool => $record->estatus !== 'abierto')
            ->form([
                Select::make('nomina_id')
                    ->label('Empleado')
                    ->options(fn ($record): array => NominaSemanal::query()
                        ->with('empleado')
                        ->where('periodo_nomina_id', $record->id)
                        ->get()
                        ->mapWithKeys(fn (NominaSemanal $nomina): array => [
                            $nomina->id => "{$nomina->empleado?->numero_empleado} - {$nomina->empleado?->nombre_completo}",
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
            ])
            ->action(fn ($record, array $data) => app(ExportarNominaOperativaService::class)->recibo(
                NominaSemanal::query()->findOrFail($data['nomina_id']),
            )),
        EditAction::make()->visible(fn ($record): bool => $record->estatus === 'abierto'),
        Action::make('generar')->label('Generar nómina')->color('success')->icon('heroicon-o-calculator')->requiresConfirmation()->visible(fn ($record): bool => $record->estatus === 'abierto')->action(function ($record): void { app(GenerarNominaSemanalService::class)->generar($record, auth()->id()); Notification::make()->title('Nómina generada')->success()->send(); }),
        Action::make('revisar')
            ->label('Marcar revisada')
            ->color('warning')
            ->icon('heroicon-o-magnifying-glass')
            ->requiresConfirmation()
            ->visible(fn ($record): bool => $record->estatus === 'generada')
            ->action(function ($record): void {
                app(GestionarPeriodoNominaService::class)->revisar($record, auth()->id());
                Notification::make()->title('Nómina marcada como revisada')->success()->send();
            }),
        Action::make('cerrar')
            ->label('Cerrar nómina')
            ->color('danger')
            ->icon('heroicon-o-lock-closed')
            ->requiresConfirmation()
            ->visible(fn ($record): bool => $record->estatus === 'revisada')
            ->action(function ($record): void {
                app(GestionarPeriodoNominaService::class)->cerrar($record, auth()->id());
                Notification::make()->title('Nómina cerrada y bloqueada')->success()->send();
            }),
    ])->toolbarActions([CreateAction::make()->label('Agregar periodo')])->defaultSort('fecha_inicio','desc'); }
}
