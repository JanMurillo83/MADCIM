<?php

namespace App\Filament\Resources\FacturasCfdi\Tables;

use App\Filament\Resources\FacturasCfdi\FacturasCfdiResource;
use App\Models\FacturasCfdi;
use App\Services\FacturaCfdiPacService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FacturasCfdiTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serie')
                    ->searchable(),
                TextColumn::make('folio')
                    ->searchable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable()
                    ->searchable()
                    ->visible(fn (): bool => Auth::user()?->isAdmin() ?? false),
                TextColumn::make('fecha_emision')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('moneda')
                    ->searchable(),
                TextColumn::make('tipo_cambio')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('impuestos_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estatus')
                    ->searchable(),
                TextColumn::make('uso_cfdi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('forma_pago')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('metodo_pago')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('regimen_fiscal_receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rfc_emisor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rfc_receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('razon_social_receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cfdi_uuid')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cfdi_estatus_sat')
                    ->label('Estatus SAT')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cfdi_estatus_cancelacion')
                    ->label('Cancelación SAT')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('documentoOrigen.folio')
                    ->label('Documento origen')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Editar')
                        ->modalWidth('full')
                        ->visible(fn (FacturasCfdi $record): bool => self::esBorrador($record))
                        ->authorize(fn (FacturasCfdi $record): bool => self::esBorrador($record)),
                    ViewAction::make()
                        ->label('Consultar')
                        ->modalWidth('full')
                        ->visible(fn (FacturasCfdi $record): bool => ! self::esBorrador($record))
                        ->authorize(fn (FacturasCfdi $record): bool => ! self::esBorrador($record)),
                    Action::make('representacion_impresa')
                        ->label('Imprimir PDF')
                        ->icon('heroicon-o-printer')
                        ->url(fn (FacturasCfdi $record): string => route('facturas-cfdi.representacion-impresa', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (FacturasCfdi $record): bool => filled($record->cfdi_uuid) && filled($record->cfdi_xml)),
                    Action::make('descargar_xml')
                        ->label('Descargar XML')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (FacturasCfdi $record): string => route('facturas-cfdi.xml', $record))
                        ->visible(fn (FacturasCfdi $record): bool => filled($record->cfdi_uuid) && filled($record->cfdi_xml)),
                    Action::make('timbrar_cfdi')
                        ->label('Timbrar / Reintentar')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Timbrar factura CFDI')
                        ->modalDescription('Se enviará el comprobante a PRODIGIA para intentar obtener el timbre fiscal.')
                        ->visible(fn (FacturasCfdi $record): bool => blank($record->cfdi_uuid)
                            && $record->estatus !== 'Cancelada')
                        ->action(function (FacturasCfdi $record): void {
                            $resultado = app(FacturaCfdiPacService::class)->timbrar($record);

                            self::notificarResultado($resultado, 'Factura timbrada', 'No se pudo timbrar');
                        }),
                    Action::make('cancelar_sin_timbrar')
                        ->label('Cancelar factura')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancelar factura sin timbre')
                        ->modalDescription('Se cancelará la factura localmente, sin enviar una solicitud a PRODIGIA. Las notas de origen quedarán disponibles nuevamente.')
                        ->visible(fn (FacturasCfdi $record): bool => blank($record->cfdi_uuid)
                            && $record->estatus !== 'Cancelada')
                        ->action(function (FacturasCfdi $record): void {
                            $resultado = app(FacturaCfdiPacService::class)->cancelarSinTimbrar($record);

                            self::notificarResultado($resultado, 'Factura cancelada', 'No se pudo cancelar');
                        }),
                    Action::make('cancelar_cfdi')
                        ->label('Cancelar CFDI')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Solicitar cancelación del CFDI')
                        ->modalDescription('La solicitud se enviará a PRODIGIA. El estado del CFDI no se marcará como cancelado hasta recibir confirmación.')
                        ->visible(fn (FacturasCfdi $record): bool => filled($record->cfdi_uuid)
                            && blank($record->cfdi_fecha_cancelacion)
                            && $record->cfdi_estatus_cancelacion !== 'Solicitud enviada')
                        ->form([
                            Select::make('motivo')
                                ->label('Motivo de cancelación')
                                ->required()
                                ->live()
                                ->options(fn (): array => DB::table('sat_motivo_cancelacion')
                                    ->orderBy('clave')
                                    ->pluck('descripcion', 'clave')
                                    ->all()),
                            TextInput::make('folio_sustitucion')
                                ->label('UUID del CFDI sustituto')
                                ->maxLength(36)
                                ->rules(['uuid'])
                                ->visible(fn (Get $get): bool => $get('motivo') === '01')
                                ->required(fn (Get $get): bool => $get('motivo') === '01'),
                        ])
                        ->action(function (FacturasCfdi $record, array $data): void {
                            $resultado = app(FacturaCfdiPacService::class)->cancelar(
                                $record,
                                (string) $data['motivo'],
                                (string) ($data['folio_sustitucion'] ?? ''),
                            );

                            self::notificarResultado($resultado, 'Solicitud de cancelación enviada', 'No se pudo cancelar');
                        }),
                ])
                    ->label('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Acciones de la factura'),
            ], RecordActionsPosition::BeforeColumns)
            ->headerActions([
                Action::make('nuevo')
                    ->label('Nuevo')
                    ->icon('fas-circle-plus')
                    ->url(fn (): string => FacturasCfdiResource::getUrl('create')),
            ], HeaderActionsPosition::Bottom);
    }

    /**
     * @param  array{success: bool, message: string}  $resultado
     */
    private static function notificarResultado(array $resultado, string $tituloExito, string $tituloError): void
    {
        $notificacion = Notification::make()
            ->title($resultado['success'] ? $tituloExito : $tituloError)
            ->body($resultado['message']);

        if ($resultado['success']) {
            $notificacion->success()->send();

            return;
        }

        $notificacion->danger()->send();
    }

    private static function esBorrador(FacturasCfdi $record): bool
    {
        return strtolower(trim((string) $record->estatus)) === 'borrador';
    }
}
