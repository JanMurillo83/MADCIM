<?php

namespace App\Filament\Resources\Documentos;

use App\Filament\Resources\Documentos\Pages\CreateDocumentos;
use App\Filament\Resources\Documentos\Pages\EditDocumentos;
use App\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Filament\Resources\Documentos\Schemas\DocumentosForm;
use App\Filament\Resources\Documentos\Tables\DocumentosTable;
use App\Models\Documentos;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DocumentosResource extends Resource
{
    protected static ?string $model = Documentos::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DocumentosForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentosTable::configure($table);
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
            'index' => ListDocumentos::route('/'),
            //'create' => CreateDocumentos::route('/create'),
            //'edit' => EditDocumentos::route('/{record}/edit'),
        ];
    }
}
