@props(['titulo', 'encabezados'])

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-4 py-3">
        <h3 class="font-semibold text-gray-950">{{ $titulo }}</h3>
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
