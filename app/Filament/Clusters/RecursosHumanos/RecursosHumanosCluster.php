<?php

namespace App\Filament\Clusters\RecursosHumanos;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class RecursosHumanosCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Recursos Humanos y Nómina';

    protected static string|null|\UnitEnum $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 4;
}
