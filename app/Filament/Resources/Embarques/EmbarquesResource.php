<?php

namespace App\Filament\Resources\Embarques;

use App\Filament\Resources\Embarques\Pages\CreateEmbarques;
use App\Filament\Resources\Embarques\Pages\EditEmbarques;
use App\Filament\Resources\Embarques\Pages\ListEmbarques;
use App\Filament\Resources\Embarques\Schemas\EmbarquesForm;
use App\Filament\Resources\Embarques\Tables\EmbarquesTable;
use App\Models\Embarque;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class EmbarquesResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = Embarque::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-truck-fast';
    protected static ?string $navigationLabel = 'Embarques';
    protected static ?string $pluralLabel = 'Embarques';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return EmbarquesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmbarquesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmbarques::route('/'),
            'create' => CreateEmbarques::route('/create'),
            'edit' => EditEmbarques::route('/{record}/edit'),
        ];
    }
}
