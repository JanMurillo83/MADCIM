<?php

namespace App\Filament\Concerns;

use App\Models\Sucursal;
use Illuminate\Support\Collection;

trait RestringeSucursal
{
    protected function sucursalEfectiva(): ?int
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        return $user->isAdmin() ? ($this->sucursal_id ?? null) : ($user->sucursal_id ? (int) $user->sucursal_id : -1);
    }

    protected function sucursalesDisponibles(): Collection
    {
        $user = auth()->user();
        $query = Sucursal::query()->orderBy('nombre');

        if ($user && !$user->isAdmin()) {
            $query->whereKey($user->sucursal_id ?? 0);
        }

        return $query->get()->mapWithKeys(fn (Sucursal $sucursal) => [$sucursal->id => $sucursal->nombre]);
    }

    protected function inicializarSucursalUsuario(): void
    {
        $user = auth()->user();
        if ($user && !$user->isAdmin()) {
            $this->sucursal_id = $user->sucursal_id ? (int) $user->sucursal_id : -1;
        }
    }
}
