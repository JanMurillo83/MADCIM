<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Horarios;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\Horarios\Pages\CreateHorario;
use App\Filament\Clusters\RecursosHumanos\Resources\Horarios\Pages\EditHorario;
use App\Filament\Clusters\RecursosHumanos\Resources\Horarios\Pages\ListHorarios;
use App\Filament\Clusters\RecursosHumanos\Resources\Horarios\Schemas\HorarioForm;
use App\Filament\Clusters\RecursosHumanos\Resources\Horarios\Tables\HorariosTable;
use App\Models\Horario;
use App\Filament\Concerns\HasRoleResourceAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HorarioResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = Horario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = RecursosHumanosCluster::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationLabel = 'Horarios';

    protected static ?string $modelLabel = 'horario';

    protected static ?string $pluralModelLabel = 'horarios';

    public static function form(Schema $schema): Schema
    {
        return HorarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HorariosTable::configure($table);
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
            'index' => ListHorarios::route('/'),
            'create' => CreateHorario::route('/create'),
            'edit' => EditHorario::route('/{record}/edit'),
        ];
    }
}
