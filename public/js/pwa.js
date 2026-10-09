window.addEventListener('load', () => {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    navigator.serviceWorker.register('/sw.js').catch(() => {
        // Pendaftaran service worker gagal; abaikan tanpa mengganggu pengguna.
    });
});
