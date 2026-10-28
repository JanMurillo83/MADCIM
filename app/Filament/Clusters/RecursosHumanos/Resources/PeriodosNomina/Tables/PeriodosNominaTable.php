<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\Tables;

use App\Models\NominaSemanal;
use App\Services\ExportarNominaOperativaService;
use App\Services\GenerarNominaSemanalService;
use App\Services\GestionarPeriodoNominaService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PeriodosNominaTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
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
            Action::make('vista_previa_pdf')
                ->label('Vista previa PDF')
                ->icon('heroicon-o-eye')
                ->visible(fn ($record): bool => $record->estatus !== 'abierto')
                ->modalHeading('Vista previa de nómina')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalContent(fn ($record) => view('filament.clusters.recursos-humanos.resources.periodos-nomina.pages.nomina-preview-modal', [
                    'title' => 'Nómina operativa',
                    'subtitle' => "Periodo: {$record->fecha_inicio->format('d/m/Y')} al {$record->fecha_fin->format('d/m/Y')}",
                    'url' => route('nomina-semanal.pdf.preview', ['periodo' => $record->id]),
                ])),
            Action::make('vista_previa_recibo')
                ->label('Vista previa recibos')
                ->icon('heroicon-o-eye')
                ->visible(fn ($record): bool => $record->estatus !== 'abierto')
                ->modalHeading('Vista previa de recibo')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalContent(function ($record) {
                    $nominas = NominaSemanal::query()
                        ->with('empleado')
                        ->where('periodo_nomina_id', $record->id)
                        ->get();

                    return view('filament.clusters.recursos-humanos.resources.periodos-nomina.pages.recibo-preview-modal', [
                        'nominas' => $nominas,
                        'urlTemplate' => route('nomina-semanal.recibo.preview', ['nomina' => '__NOMINA__']),
                    ]);
                }),
            EditAction::make()->visible(fn ($record): bool => $record->estatus === 'abierto'),
            Action::make('generar')->label('Generar nómina')->color('success')->icon('heroicon-o-calculator')->requiresConfirmation()->visible(fn ($record): bool => $record->estatus === 'abierto')->action(function ($record): void {
                app(GenerarNominaSemanalService::class)->generar($record, auth()->id());
                Notification::make()->title('Nómina generada')->success()->send();
            }),
            Action::make('regenerar')
                ->label('Generar de nuevo')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Generar nómina nuevamente')
                ->modalDescription('Se reemplazarán los detalles calculados de esta nómina. Los conceptos manuales del periodo se conservarán.')
                ->visible(fn ($record): bool => in_array($record->estatus, ['generada', 'revisada'], true))
                ->action(function ($record): void {
                    app(GestionarPeriodoNominaService::class)->regenerar($record, auth()->id());
                    Notification::make()->title('Nómina generada nuevamente')->success()->send();
                }),
            DeleteAction::make()
                ->label('Eliminar nómina')
                ->requiresConfirmation()
                ->visible(fn ($record): bool => $record->estatus !== 'cerrada')
                ->action(function ($record): void {
                    app(GestionarPeriodoNominaService::class)->eliminar($record);
                    Notification::make()->title('Periodo de nómina eliminado')->success()->send();
                }),
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
        ])->toolbarActions([CreateAction::make()->label('Agregar periodo')])->defaultSort('fecha_inicio', 'desc');
    }
}
