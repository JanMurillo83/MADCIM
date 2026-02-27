<?php

namespace App\Filament\Resources\Usuarios\Schemas;

use App\Models\Sucursal;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UsuariosForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del usuario')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Contrasena')
                            ->password()
                            ->revealable()
                            ->required(fn (string $context) => $context === 'create')
                            ->minLength(6),
                        Select::make('role')
                            ->label('Rol')
                            ->required()
                            ->options([
                                'Administrador' => 'Administrador',
                                'Supervisor' => 'Supervisor',
                                'Cajero' => 'Cajero',
                                'Vendedor' => 'Vendedor',
                                'Almacen' => 'Almacen',
                                'Entregas' => 'Entregas',
                            ])
                            ->default('Vendedor')
                            ->live(),
                        Select::make('sucursal_id')
                            ->label('Sucursal')
                            ->options(Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get) => $get('role') !== 'Administrador')
                            ->visible(fn (Get $get) => $get('role') !== 'Administrador'),
                    ])
                    ->columns(2),
            ]);
    }
}
