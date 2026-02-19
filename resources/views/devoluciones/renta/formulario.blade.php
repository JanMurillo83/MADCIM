<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devolución de Renta - {{ $nota->folio }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Devolución de Items en Renta</h1>

            <div class="mb-6 p-4 bg-blue-50 rounded">
                <h2 class="font-semibold text-lg mb-2">Información de la Nota</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p><span class="font-medium">Folio:</span> {{ $nota->folio }}</p>
                        <p><span class="font-medium">Cliente:</span> {{ $nota->cliente->nombre }}</p>
                    </div>
                    <div>
                        <p><span class="font-medium">Fecha Emisión:</span> {{ $nota->fecha_emision->format('d/m/Y') }}</p>
                        <p><span class="font-medium">Estatus:</span> {{ $nota->estatus }}</p>
                    </div>
                </div>
            </div>

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('notas-venta-renta.devolucion.procesar', $nota->id) }}" method="POST">
                @csrf

                <div class="mb-6">
                    <h3 class="font-semibold text-lg mb-4">Items Rentados</h3>
                    <table class="w-full border-collapse border border-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border border-gray-300 px-4 py-2">Producto</th>
                                <th class="border border-gray-300 px-4 py-2">Cantidad Rentada</th>
                                <th class="border border-gray-300 px-4 py-2">Cantidad Devuelta</th>
                                <th class="border border-gray-300 px-4 py-2">Precio Unitario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($itemsRentados as $item)
                                <tr>
                                    <td class="border border-gray-300 px-4 py-2">{{ $item->producto->descripcion }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-center">{{ $item->cantidad }}</td>
                                    <td class="border border-gray-300 px-4 py-2">
                                        <input
                                            type="number"
                                            name="items[{{ $item->id }}][cantidad_devuelta]"
                                            value="{{ $item->cantidad }}"
                                            min="0"
                                            max="{{ $item->cantidad }}"
                                            class="w-full px-2 py-1 border rounded"
                                            required
                                        >
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">
                                        ${{ number_format($item->producto->precio_venta, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center">
                    <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Cancelar
                    </a>
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        Procesar Devolución
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
