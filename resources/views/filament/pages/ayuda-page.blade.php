<x-filament-panels::page>
    <div
        class="min-h-[60vh] bg-center bg-no-repeat bg-contain"
        style="background-image: url('{{ asset('images/LOGO.png') }}');"
    ></div>
    <x-filament-actions::modals />

    <script>
        document.addEventListener('livewire:init', () => {
            function updateButtonsVisibility(currentTab) {
                // Obtener todos los botones del footer
                const btnCotizacion = document.querySelector('[wire\\:click="mountAction(\'guardar_cotizacion\')"]')?.closest('button');
                const btnNotaRenta = document.querySelector('[wire\\:click="mountAction(\'guardar_nota_renta\')"]')?.closest('button');
                const btnNotaVenta = document.querySelector('[wire\\:click="mountAction(\'guardar_nota_venta\')"]')?.closest('button');

                // Guardar como Cotización - deshabilitado en tabs 2 y 3
                if (btnCotizacion) {
                    if ([2, 3].includes(currentTab)) {
                        btnCotizacion.style.display = 'none';
                    } else {
                        btnCotizacion.style.display = '';
                    }
                }

                // Guardar como Nota Renta - deshabilitado en tabs 2, 3 y 4
                if (btnNotaRenta) {
                    if ([2, 3, 4].includes(currentTab)) {
                        btnNotaRenta.style.display = 'none';
                    } else {
                        btnNotaRenta.style.display = '';
                    }
                }

                // Guardar como Nota Venta - deshabilitado en tabs 1, 2 y 3
                if (btnNotaVenta) {
                    if ([1, 2, 3].includes(currentTab)) {
                        btnNotaVenta.style.display = 'none';
                    } else {
                        btnNotaVenta.style.display = '';
                    }
                }
            }

            // Escuchar cambios de tab
            Livewire.on('tab-changed', (event) => {
                updateButtonsVisibility(event.tab);
            });

            // Escuchar evento de redirect
            Livewire.on('redirect', (url) => {
                window.location.href = url;
            });

            // Establecer visibilidad inicial al abrir el modal
            setTimeout(() => {
                updateButtonsVisibility(1); // Tab inicial es Principal (1)
            }, 100);
        });
    </script>
</x-filament-panels::page>
