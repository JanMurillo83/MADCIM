<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($this->reportes as $reporte)
                <div class="bg-white rounded-xl shadow border border-gray-200 p-5 flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ $reporte['titulo'] }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ $reporte['descripcion'] }}</p>
                    </div>
                    <div class="mt-4">
                        <x-filament::button :href="$reporte['url']" tag="a" color="primary">
                            Abrir reporte
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
