(function () {
    'use strict';

    const registrationScript = document.currentScript;
    const appRoot = registrationScript
        ? new URL('../', registrationScript.src)
        : new URL('./', window.location.href);
    let deferredInstallPrompt = null;

    function syncWindowControlsOverlay() {
        const overlay = navigator.windowControlsOverlay;
        const overlayVisible = Boolean(overlay && overlay.visible);
        document.documentElement.classList.toggle(
            'aves-window-controls-overlay',
            overlayVisible
        );
    }

    if ('windowControlsOverlay' in navigator) {
        syncWindowControlsOverlay();
        navigator.windowControlsOverlay.addEventListener(
            'geometrychange',
            syncWindowControlsOverlay
        );
    }

    function installButtons() {
        return document.querySelectorAll('[data-pwa-install]');
    }

    function setInstallButtonsVisible(visible) {
        installButtons().forEach(function (button) {
            button.hidden = !visible;
            button.disabled = !visible;
            button.style.display = visible ? 'inline-flex' : 'none';
        });
    }

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            const workerUrl = new URL('service-worker.js', appRoot);
            navigator.serviceWorker.register(workerUrl.pathname, {
                scope: appRoot.pathname
            }).catch(function (error) {
                console.error('Pendaftaran PWA gagal:', error);
            });
        });
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredInstallPrompt = event;
        setInstallButtonsVisible(true);
    });

    document.addEventListener('click', async function (event) {
        const installButton = event.target.closest('[data-pwa-install]');
        if (!installButton || !deferredInstallPrompt) {
            return;
        }

        installButton.disabled = true;
        await deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        setInstallButtonsVisible(false);
    });

    window.addEventListener('appinstalled', function () {
        deferredInstallPrompt = null;
        setInstallButtonsVisible(false);
    });

    setInstallButtonsVisible(false);
})();
