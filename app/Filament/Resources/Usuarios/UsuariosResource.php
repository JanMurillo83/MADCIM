<?php

namespace App\Filament\Resources\Usuarios;

use App\Filament\Resources\Usuarios\Pages\CreateUsuarios;
use App\Filament\Resources\Usuarios\Pages\EditUsuarios;
use App\Filament\Resources\Usuarios\Pages\ListUsuarios;
use App\Filament\Resources\Usuarios\Schemas\UsuariosForm;
use App\Filament\Resources\Usuarios\Tables\UsuariosTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class UsuariosResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-users-cog';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $pluralLabel = 'Usuarios';

    protected static string|null|\UnitEnum $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return UsuariosForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsuariosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsuarios::route('/'),
            'create' => CreateUsuarios::route('/create'),
            'edit' => EditUsuarios::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
