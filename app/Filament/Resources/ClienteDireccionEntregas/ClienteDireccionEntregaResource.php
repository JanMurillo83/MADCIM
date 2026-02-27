<?php

namespace App\Filament\Resources\ClienteDireccionEntregas;

use App\Filament\Resources\ClienteDireccionEntregas\Pages\CreateClienteDireccionEntrega;
use App\Filament\Resources\ClienteDireccionEntregas\Pages\EditClienteDireccionEntrega;
use App\Filament\Resources\ClienteDireccionEntregas\Pages\ListClienteDireccionEntregas;
use App\Filament\Resources\ClienteDireccionEntregas\Schemas\ClienteDireccionEntregaForm;
use App\Filament\Resources\ClienteDireccionEntregas\Tables\ClienteDireccionEntregasTable;
use App\Models\ClienteDireccionEntrega;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class ClienteDireccionEntregaResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = ClienteDireccionEntrega::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Direcciones de Entrega';

    protected static ?string $modelLabel = 'Dirección de Entrega';

    protected static ?string $pluralModelLabel = 'Direcciones de Entrega';

    //protected static string|null|\UnitEnum $navigationGroup = 'Clientes';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return ClienteDireccionEntregaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClienteDireccionEntregasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClienteDireccionEntregas::route('/'),
            'create' => CreateClienteDireccionEntrega::route('/create'),
            'edit' => EditClienteDireccionEntrega::route('/{record}/edit'),
        ];
    }
}
