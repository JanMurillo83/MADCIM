<?php

namespace App\Filament\Resources\Clientes\Tables;

use App\Models\SatRegimenFiscal;
use App\Services\ClientesImportService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use App\Models\ClienteDireccionEntrega;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid as FormGrid;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Forms\Components\Checkbox as FormCheckbox;
use Filament\Forms\Components\Textarea as FormTextarea;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clave')
                    ->searchable(),
                TextColumn::make('nombre')
                    ->searchable(),
                TextColumn::make('rfc')
                    ->searchable(),
                TextColumn::make('regimen')
                    ->label('Régimen fiscal')
                    ->formatStateUsing(function (?string $state): ?string {
                        if ($state === null || $state === '') {
                            return $state;
                        }

                        static $map = null;
                        $map ??= SatRegimenFiscal::query()->pluck('descripcion', 'clave')->all();

                        $descripcion = $map[$state] ?? null;

                        return $descripcion ? "{$state} - {$descripcion}" : $state;
                    })
                    ->searchable(),
                TextColumn::make('codigo')
                    ->searchable(),
                TextColumn::make('calle')
                    ->searchable(),
                TextColumn::make('exterior')
                    ->searchable(),
                TextColumn::make('interior')
                    ->searchable(),
                TextColumn::make('colonia')
                    ->searchable(),
                TextColumn::make('municipio')
                    ->searchable(),
                TextColumn::make('estado')
                    ->searchable(),
                TextColumn::make('pais')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->searchable(),
                TextColumn::make('correo')
                    ->searchable(),
                TextColumn::make('descuento')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lista')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('contacto')
                    ->searchable(),
                TextColumn::make('dias_credito')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('saldo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    \Filament\Actions\Action::make('direccionesEntrega')
                        ->label('Direcciones de Entrega')
                        ->icon('fas-truck-loading')
                        ->modalHeading(fn ($record) => "Direcciones de Entrega - {$record->nombre}")
                        ->modalWidth('7xl')
                        ->fillForm(fn ($record) => [
                            'direcciones' => $record->direccionesEntrega->toArray(),
                        ])
                        ->form([
                            Repeater::make('direcciones')
                                ->relationship('direccionesEntrega')
                                ->collapsed()
                                ->label(false)
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            FormTextInput::make('nombre_direccion')
                                                ->label('Nombre de la Dirección')
                                                ->placeholder('Ej: Oficina Principal, Almacén 2')
                                                ->required()
                                                ->maxLength(255),
                                            FormCheckbox::make('activa')
                                                ->label('Activa')
                                                ->default(true),
                                            FormCheckbox::make('es_principal')
                                                ->label('Dirección Principal'),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            FormTextInput::make('calle')
                                                ->label('Calle')
                                                ->required()
                                                ->maxLength(255),
                                            FormTextInput::make('numero_exterior')
                                                ->label('Número Exterior')
                                                ->required()
                                                ->maxLength(255),
                                            FormTextInput::make('numero_interior')
                                                ->label('Número Interior')
                                                ->maxLength(255),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            FormTextInput::make('colonia')
                                                ->label('Colonia')
                                                ->required()
                                                ->maxLength(255),
                                            FormTextInput::make('municipio')
                                                ->label('Municipio')
                                                ->required()
                                                ->maxLength(255),
                                            FormTextInput::make('estado')
                                                ->label('Estado')
                                                ->required()
                                                ->maxLength(255),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            FormTextInput::make('codigo_postal')
                                                ->label('Código Postal')
                                                ->required()
                                                ->maxLength(255),
                                            FormTextInput::make('pais')
                                                ->label('País')
                                                ->default('México')
                                                ->required()
                                                ->maxLength(255),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            FormTextInput::make('contacto_nombre')
                                                ->label('Nombre del Contacto')
                                                ->maxLength(255),
                                            FormTextInput::make('contacto_telefono')
                                                ->label('Teléfono del Contacto')
                                                ->tel()
                                                ->maxLength(255),
                                        ]),
                                    Textarea::make('referencias')
                                        ->label('Referencias')
                                        ->rows(2)
                                        ->maxLength(65535),
                                ])
                                ->addActionLabel('Añadir Dirección')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['nombre_direccion'] ?? null),
                        ])
                        ->action(function ($record, array $data) {
                            // La relación se maneja automáticamente por el Repeater con relationship('direccionesEntrega')
                            Notification::make()
                                ->title('Direcciones actualizadas')
                                ->success()
                                ->send();
                        })
                        ->modalSubmitActionLabel('Guardar cambios'),
                ]),
            ],RecordActionsPosition::BeforeColumns)
            ->headerActions([
                \Filament\Actions\Action::make('descargarLayout')
                    ->label('Descargar layout')
                    ->icon('fas-file-arrow-down')
                    ->action(function () {
                        $headers = ClientesImportService::HEADERS;
                        $csv = implode(',', $headers) . "\n";

                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, 'clientes_layout.csv', ['Content-Type' => 'text/csv']);
                    }),
                \Filament\Actions\Action::make('importar')
                    ->label('Importar')
                    ->icon('fas-file-import')
                    ->modalHeading('Importar clientes')
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
                            ->directory('imports/clientes')
                            ->preserveFilenames(),
                    ])
                    ->action(function (array $data) {
                        $importer = app(ClientesImportService::class);
                        $disk = Storage::disk('local');
                        $path = $disk->path($data['archivo']);

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
                            $disk->delete($data['archivo']);
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
