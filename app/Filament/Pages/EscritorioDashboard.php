<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;

class EscritorioDashboard extends Dashboard
{
    protected static ?string $title = 'Escritorio';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }
}
