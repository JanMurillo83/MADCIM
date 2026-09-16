(() => {
    let modal = null;
    let stream = null;

    const getFileInput = () => document.querySelector('[data-ine-upload] input[type="file"]');

    const stopStream = () => {
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
    };

    const closeModal = () => {
        stopStream();
        modal?.remove();
        modal = null;
    };

    const showMessage = (message) => {
        const element = modal?.querySelector('[data-ine-camera-message]');
        if (element) {
            element.textContent = message;
        }
    };

    const openModal = async () => {
        if (modal) {
            return;
        }

        modal = document.createElement('div');
        modal.className = 'ine-camera-modal';
        modal.innerHTML = `
            <div class="ine-camera-dialog" role="dialog" aria-modal="true" aria-labelledby="ine-camera-title">
                <div class="ine-camera-header">
                    <h2 id="ine-camera-title">Tomar foto de INE</h2>
                    <button type="button" data-ine-camera-close aria-label="Cerrar">&times;</button>
                </div>
                <video data-ine-camera-video autoplay playsinline></video>
                <canvas data-ine-camera-canvas hidden></canvas>
                <p data-ine-camera-message class="ine-camera-message">Solicitando acceso a la camara...</p>
                <div class="ine-camera-actions">
                    <button type="button" data-ine-camera-close class="ine-camera-secondary">Cancelar</button>
                    <button type="button" data-ine-camera-capture class="ine-camera-primary" disabled>Capturar</button>
                </div>
            </div>
        `;
        document.body.append(modal);

        try {
            if (!navigator.mediaDevices?.getUserMedia) {
                throw new Error('Este navegador no permite usar la camara.');
            }

            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } },
                audio: false,
            });

            const video = modal.querySelector('[data-ine-camera-video]');
            video.srcObject = stream;
            await video.play();
            modal.querySelector('[data-ine-camera-capture]').disabled = false;
            showMessage('Alinea la INE y captura la imagen.');
        } catch (error) {
            showMessage(error.message || 'No fue posible acceder a la camara.');
        }
    };

    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-ine-camera-trigger]');
        if (trigger) {
            event.preventDefault();
            await openModal();
            return;
        }

        if (event.target.closest('[data-ine-camera-close]')) {
            closeModal();
            return;
        }

        if (!event.target.closest('[data-ine-camera-capture]')) {
            return;
        }

        const video = modal?.querySelector('[data-ine-camera-video]');
        const canvas = modal?.querySelector('[data-ine-camera-canvas]');
        const input = getFileInput();

        if (!video || !canvas || !input || video.videoWidth === 0) {
            showMessage('La camara aun no esta lista.');
            return;
        }

        const scale = Math.min(1, 2000 / video.videoWidth);
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => {
            if (!blob) {
                showMessage('No fue posible generar la imagen.');
                return;
            }

            const file = new File([blob], 'ine-camera.jpg', { type: 'image/jpeg' });
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            closeModal();
        }, 'image/jpeg', 0.85);
    });
})();