<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Checadas;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\Checadas\Pages\ListChecadas;
use App\Filament\Clusters\RecursosHumanos\Resources\Checadas\Tables\ChecadasTable;
use App\Models\Checada;
use App\Filament\Concerns\HasRoleResourceAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChecadaResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = Checada::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = RecursosHumanosCluster::class;

    protected static ?string $recordTitleAttribute = 'empleado_id';

    protected static ?string $navigationLabel = 'Checadas';

    protected static ?string $modelLabel = 'checada';

    protected static ?string $pluralModelLabel = 'checadas';

    public static function table(Table $table): Table
    {
        return ChecadasTable::configure($table);
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
            'index' => ListChecadas::route('/'),
        ];
    }
}
