<?php

namespace App\Filament\Resources\Productos\Schemas;

use App\Models\Grupos;
use App\Models\Lineas;
use App\Models\Productos;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;


class ProductosForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('clave')
                    ->required()
                    ->readOnly(fn (?string $operation): bool => $operation === 'edit')
                    ->columnSpan(2),
                TextInput::make('descripcion')
                    ->required()->columnSpanFull(),
                Select::make('clave_prod_serv')
                    ->label('Clave SAT de producto o servicio')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => DB::table('sat_clave_prod_serv')
                        ->where(function ($query) use ($search): void {
                            $query->where('clave', 'like', "%{$search}%")
                                ->orWhere('descripcion', 'like', "%{$search}%")
                                ->orWhere('palabras_similares', 'like', "%{$search}%");
                        })
                        ->orderBy('clave')
                        ->limit(50)
                        ->get(['clave', 'descripcion'])
                        ->mapWithKeys(fn ($catalogo): array => [
                            $catalogo->clave => $catalogo->clave . ' - ' . $catalogo->descripcion,
                        ])
                        ->all())
                    ->getOptionLabelUsing(function ($value): ?string {
                        if (!$value) {
                            return null;
                        }

                        $descripcion = DB::table('sat_clave_prod_serv')->where('clave', $value)->value('descripcion');

                        return $descripcion ? $value . ' - ' . $descripcion : $value;
                    }),
                Hidden::make('unidad_sat'),
                Select::make('clave_unidad')
                    ->label('Unidad SAT')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => DB::table('sat_clave_unidad')
                        ->where(function ($query) use ($search): void {
                            $query->where('clave', 'like', "%{$search}%")
                                ->orWhere('nombre', 'like', "%{$search}%")
                                ->orWhere('descripcion', 'like', "%{$search}%")
                                ->orWhere('simbolo', 'like', "%{$search}%");
                        })
                        ->orderBy('clave')
                        ->limit(50)
                        ->get(['clave', 'nombre', 'simbolo'])
                        ->mapWithKeys(fn ($catalogo): array => [
                            $catalogo->clave => trim($catalogo->clave . ' - ' . $catalogo->nombre . ($catalogo->simbolo ? ' (' . $catalogo->simbolo . ')' : '')),
                        ])
                        ->all())
                    ->getOptionLabelUsing(function ($value): ?string {
                        if (!$value) {
                            return null;
                        }

                        $catalogo = DB::table('sat_clave_unidad')->where('clave', $value)->first(['nombre', 'simbolo']);

                        return $catalogo
                            ? trim($value . ' - ' . $catalogo->nombre . ($catalogo->simbolo ? ' (' . $catalogo->simbolo . ')' : ''))
                            : $value;
                    })
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $unidad = DB::table('sat_clave_unidad')->where('clave', $state)->first(['nombre', 'simbolo']);
                        $set('unidad_sat', $unidad?->nombre ?: $unidad?->simbolo);
                    }),
                TextInput::make('m2_cubre')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('costo')
                    ->label('Costo promedio')
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->default(0.0)->readOnly(),
                TextInput::make('ultimo_costo')
                    ->label('Ultimo costo')
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->default(0.0)->readOnly(),
                TextInput::make('precio_venta')
                    ->label('Precio de Venta')
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('precio_renta_mes')
                    ->required()
                    ->label('Precio Renta Mensual')
                    ->prefix('$')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('precio_renta_dia')
                    ->required()
                    ->label('Precio Renta Diaria')
                    ->prefix('$')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('precio_renta_semana')
                    ->required()
                    ->label('Precio Renta Semanal')
                    ->prefix('$')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('existencia')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                Select::make('grupo')
                    ->options(Grupos::all()->pluck('nombre', 'nombre'))
                    ->required(),
                Select::make('linea')
                    ->options(Lineas::all()->pluck('nombre', 'nombre'))
                    ->required(),
                Hidden::make('largo')
                    ->default(0.0),
                Hidden::make('ancho')
                    ->default(0.0),
                FileUpload::make('imagen')
                    ->disk('public')
                    ->image()
                    ->directory('productos')
                    ->downloadable()
            ])->columns(5);
    }
}
