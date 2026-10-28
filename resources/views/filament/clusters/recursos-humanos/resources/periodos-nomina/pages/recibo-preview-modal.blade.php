<div
    x-data="{ url: '' }"
    class="space-y-4"
>
    <label class="block text-sm font-medium text-gray-700" for="nomina-preview-select">Empleado</label>
    <select
        id="nomina-preview-select"
        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
        x-on:change="url = $event.target.value"
    >
        <option value="">Selecciona un empleado</option>
        @foreach($nominas as $nomina)
            <option value="{{ str_replace('__NOMINA__', (string) $nomina->id, $urlTemplate) }}">
                {{ $nomina->empleado?->numero_empleado }} - {{ $nomina->empleado?->nombre_completo }}
            </option>
        @endforeach
    </select>

    <div class="rounded-lg border border-gray-200 bg-white">
        <div x-show="!url" class="flex min-h-[28rem] items-center justify-center text-sm text-gray-500">
            Selecciona un empleado para generar la vista previa.
        </div>
        <iframe
            x-show="url"
            :src="url"
            title="Recibo de nómina"
            class="h-[65vh] min-h-[28rem] w-full rounded-lg border-0 bg-white"
        ></iframe>
    </div>
</div>
