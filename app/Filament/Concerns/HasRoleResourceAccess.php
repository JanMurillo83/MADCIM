<?php

namespace App\Filament\Concerns;

trait HasRoleResourceAccess
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessResource(static::class) ?? false;
    }
}
