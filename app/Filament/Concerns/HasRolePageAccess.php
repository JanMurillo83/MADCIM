<?php

namespace App\Filament\Concerns;

trait HasRolePageAccess
{
    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessPage(static::class) ?? false;
    }
}
