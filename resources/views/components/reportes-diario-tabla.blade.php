@props(['titulo', 'encabezados', 'icon' => 'heroicon-o-document-text'])

<div class="rd-table-card">
    <div class="rd-table-heading">
        <h3><span class="rd-table-icon"><x-dynamic-component :component="$icon" /></span>{{ $titulo }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($encabezados as $encabezado)
                        <th class="whitespace-nowrap px-4 py-2 text-left font-medium text-gray-600">{{ $encabezado }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
