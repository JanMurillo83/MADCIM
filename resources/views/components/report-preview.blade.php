@props([
    'title',
    'subtitle' => null,
    'pdfAction' => 'exportPdf',
    'excelAction' => 'exportExcel',
    'previewView' => null,
    'previewData' => [],
])

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false">
    <x-filament::button type="button" color="gray" icon="heroicon-o-eye" x-on:click="open = true">
        Vista previa
    </x-filament::button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="{{ $title }}"
        x-on:click.self="open = false"
    >
        <div
            x-show="open"
            x-transition
            class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-gray-100 shadow-2xl"
        >
            <header class="flex items-center justify-between border-b border-gray-200 bg-white px-5 py-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-950">{{ $title }}</h2>
                    @if($subtitle)
                        <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
                    @endif
                </div>
                <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900" x-on:click="open = false" aria-label="Cerrar vista previa">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>
            </header>

            <main class="overflow-y-auto p-5">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    @if($previewView)
                        @include($previewView, $previewData)
                    @else
                        {{ $slot }}
                    @endif
                </div>
            </main>

            <footer class="flex flex-wrap justify-end gap-2 border-t border-gray-200 bg-white px-5 py-4">
                <x-filament::button type="button" color="danger" icon="heroicon-o-document-arrow-down" wire:click="{{ $pdfAction }}">
                    Guardar PDF
                </x-filament::button>
                <x-filament::button type="button" color="success" icon="heroicon-o-table-cells" wire:click="{{ $excelAction }}">
                    Guardar Excel
                </x-filament::button>
            </footer>
        </div>
    </div>
</div>
