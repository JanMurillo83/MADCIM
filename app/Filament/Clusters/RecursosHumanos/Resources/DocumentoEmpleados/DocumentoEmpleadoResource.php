<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\Pages\CreateDocumentoEmpleado;
use App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\Pages\EditDocumentoEmpleado;
use App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\Pages\ListDocumentoEmpleados;
use App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\Schemas\DocumentoEmpleadoForm;
use App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\Tables\DocumentoEmpleadosTable;
use App\Models\DocumentoEmpleado;
use App\Filament\Concerns\HasRoleResourceAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DocumentoEmpleadoResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = DocumentoEmpleado::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = RecursosHumanosCluster::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationLabel = 'Documentos laborales';

    protected static string|null|\UnitEnum $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'documento laboral';

    protected static ?string $pluralModelLabel = 'documentos laborales';

    public static function form(Schema $schema): Schema
    {
        return DocumentoEmpleadoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentoEmpleadosTable::configure($table);
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
            'index' => ListDocumentoEmpleados::route('/'),
            'create' => CreateDocumentoEmpleado::route('/create'),
            'edit' => EditDocumentoEmpleado::route('/{record}/edit'),
        ];
    }
}
