<?php

namespace App\Filament\Resources\Productos\Tables;

use App\Models\Productos;
use App\Services\ProductosImportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->recordAction('view')
            ->columns([
                TextColumn::make('clave')
                    ->searchable(),
                TextColumn::make('descripcion')
                    ->searchable(),
                TextColumn::make('precio_renta_dia')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->prefix('$')->alignRight()
                    ->sortable(),
                TextColumn::make('precio_renta_semana')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->prefix('$')->alignRight()
                    ->sortable(),
                TextColumn::make('precio_renta_mes')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->prefix('$')->alignRight()
                    ->sortable(),
                TextColumn::make('costo')
                    ->label('Costo promedio')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->prefix('$')->alignRight()
                    ->sortable(),
                TextColumn::make('ultimo_costo')
                    ->label('Ultimo costo')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->prefix('$')->alignRight()
                    ->sortable(),
                TextColumn::make('precio_venta')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->prefix('$')->alignRight()
                    ->sortable(),
                TextColumn::make('m2_cubre')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('existencia')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('grupo')
                    ->searchable(),
                TextColumn::make('linea')
                    ->searchable(),
                TextColumn::make('largo')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('ancho')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->alignRight()
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Consultar')
                        ->modalWidth('7xl'),
                    EditAction::make()
                    ->modalWidth('7xl')
                    ->modalSubmitAction(function ($action) {
                        $action->icon('fas-floppy-disk');
                        $action->label('Guardar');
                        $action->extraAttributes(['style' => 'width: 150px !important;']);
                        $action->color('success');
                        return $action;
                    })->modalCancelAction(function ($action) {
                        $action->icon('fas-ban');
                        $action->label('Cancelar');
                        $action->extraAttributes(['style' => 'width: 150px !important;']);
                        $action->color('danger');
                        return $action;
                    }),
                ])
            ],RecordActionsPosition::BeforeColumns)
            ->headerActions([
                Action::make('exportarConsulta')
                    ->label('Exportar Excel (CSV)')
                    ->icon('fas-file-export')
                    ->action(function (HasTable $livewire) {
                        $query = $livewire->getTableQueryForExport();
                        $productos = $query->get([
                            'clave',
                            'descripcion',
                            'm2_cubre',
                            'costo',
                            'ultimo_costo',
                            'precio_venta',
                            'precio_renta_mes',
                            'precio_renta_dia',
                            'precio_renta_semana',
                            'existencia',
                            'grupo',
                            'linea',
                            'largo',
                            'ancho',
                        ]);

                        $rows = [];
                        $rows[] = ProductosImportService::HEADERS;

                        foreach ($productos as $producto) {
                            $rows[] = [
                                $producto->clave,
                                $producto->descripcion,
                                number_format((float) $producto->m2_cubre, 8, '.', ''),
                                number_format((float) $producto->costo, 8, '.', ''),
                                number_format((float) $producto->ultimo_costo, 8, '.', ''),
                                number_format((float) $producto->precio_venta, 8, '.', ''),
                                number_format((float) $producto->precio_renta_mes, 8, '.', ''),
                                number_format((float) $producto->precio_renta_dia, 8, '.', ''),
                                number_format((float) $producto->precio_renta_semana, 8, '.', ''),
                                number_format((float) $producto->existencia, 8, '.', ''),
                                $producto->grupo,
                                $producto->linea,
                                number_format((float) $producto->largo, 8, '.', ''),
                                number_format((float) $producto->ancho, 8, '.', ''),
                            ];
                        }

                        $filename = 'productos_consulta_' . now()->format('Ymd_His') . '.csv';

                        return response()->streamDownload(function () use ($rows) {
                            $handle = fopen('php://output', 'w');
                            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                            foreach ($rows as $row) {
                                fputcsv($handle, $row);
                            }

                            fclose($handle);
                        }, $filename, [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    }),
                Action::make('importar')
                    ->label('Importar Excel (CSV)')
                    ->icon('fas-file-import')
                    ->modalHeading('Importar productos')
                    ->form([
                        FileUpload::make('archivo')
                            ->label('Archivo Excel/CSV')
                            ->required()
                            ->acceptedFileTypes([
                                'text/csv',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->disk('local')
                            ->directory('imports/productos')
                            ->preserveFilenames(),
                    ])
                    ->action(function (array $data) {
                        $archivo = $data['archivo'] ?? null;

                        if (is_array($archivo)) {
                            $archivo = reset($archivo) ?: null;
                        }

                        if (!$archivo) {
                            Notification::make()
                                ->title('Error al importar')
                                ->body('No se recibio archivo para importar.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $importer = app(ProductosImportService::class);
                        $disk = Storage::disk('local');
                        $path = $disk->path($archivo);

                        try {
                            [$insertados, $actualizados] = $importer->importFromPath($path);

                            Notification::make()
                                ->title('Importacion completa')
                                ->body("Insertados: {$insertados}. Actualizados: {$actualizados}.")
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Error al importar')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        } finally {
                            $disk->delete($archivo);
                        }
                    }),
                CreateAction::make()
                ->createAnother(false)
                ->label('Nuevo')
                ->icon('fas-circle-plus')
                ->modalWidth('7xl')
                ->modalSubmitAction(function ($action) {
                    $action->icon('fas-floppy-disk');
                    $action->label('Guardar');
                    $action->extraAttributes(['style' => 'width: 150px !important;']);
                    $action->color('success');
                    return $action;
                })->modalCancelAction(function ($action) {
                    $action->icon('fas-ban');
                    $action->label('Cancelar');
                    $action->extraAttributes(['style' => 'width: 150px !important;']);
                    $action->color('danger');
                    return $action;
                }),
            ],HeaderActionsPosition::Bottom);
    }
}
