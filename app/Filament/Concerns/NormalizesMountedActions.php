<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Log;

trait NormalizesMountedActions
{
    protected function normalizeMountedActions(): void
    {
        $normalizedActions = [];
        $invalidEntries = [];

        foreach ($this->mountedActions ?? [] as $key => $action) {
            if (! is_array($action) || ! is_string($action['name'] ?? null) || $action['name'] === '') {
                $invalidEntries[] = [
                    'key' => $key,
                    'type' => get_debug_type($action),
                ];

                continue;
            }

            $normalizedActions[] = $action;
        }

        if ($invalidEntries !== []) {
            Log::warning('Discarded malformed mounted action state.', [
                'component' => static::class,
                'invalid_entries' => $invalidEntries,
            ]);
        }

        $this->mountedActions = $normalizedActions;
    }
}
