<?php

namespace App\Filament\Resources\Configuracion;

use App\Filament\Concerns\HasRoleResourceAccess;
use App\Filament\Resources\Configuracion\Pages\EditConfiguracion;
use App\Filament\Resources\Configuracion\Pages\ListConfiguracion;
use App\Filament\Resources\Configuracion\Schemas\ConfiguracionForm;
use App\Filament\Resources\Configuracion\Tables\ConfiguracionTable;
use App\Models\Configuracion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ConfiguracionResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = Configuracion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configuracion';
    protected static ?string $pluralLabel = 'Configuracion';
    protected static string|null|\UnitEnum $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ConfiguracionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConfiguracionTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConfiguracion::route('/'),
            'edit' => EditConfiguracion::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
