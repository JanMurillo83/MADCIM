<?php

namespace App\Filament\Concerns;

trait HasRolePageAccess
{
    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessPage(static::class) ?? false;
    }

    public function previewPdf(): void
    {
        if (! method_exists($this, 'exportPdf')) {
            throw new \LogicException('La página no tiene una exportación PDF configurada.');
        }

        $response = $this->exportPdf();
        if (! $response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            throw new \LogicException('La exportación PDF no devolvió una respuesta transmitida.');
        }

        $callback = $response->getCallback();
        if (! $callback) {
            throw new \LogicException('La exportación PDF no tiene contenido para la vista previa.');
        }

        ob_start();
        $callback();
        $content = ob_get_clean();

        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('No fue posible generar el PDF para la vista previa.');
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        foreach ($disk->files('report-previews') as $oldFile) {
            if ($disk->lastModified($oldFile) < now()->subHour()->getTimestamp()) {
                $disk->delete($oldFile);
            }
        }

        $token = (string) \Illuminate\Support\Str::uuid();
        $disk->put("report-previews/{$token}.pdf", $content);

        $this->dispatch('report-preview-ready', url: route('report-preview.file', ['token' => $token]));
    }
}
