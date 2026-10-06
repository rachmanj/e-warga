<script>
    (() => {
        'use strict';

        window._EwargaReady = (callback) => {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback, { once: true });
            } else {
                callback();
            }
        };

        window._EwargaOnce = (key, callback) => {
            window._EwargaOnceKeys = window._EwargaOnceKeys || {};

            if (window._EwargaOnceKeys[key]) {
                return;
            }

            window._EwargaOnceKeys[key] = true;
            callback();
        };

        window._AdminLTE_Ready = window._EwargaReady;
        window._AdminLTE_Once = window._EwargaOnce;

        @if($spaNavigation)
        window._EwargaOnce('spa-navigation', () => {
            document.addEventListener('livewire:navigated', () => {
                if (typeof adminlte !== 'undefined' && typeof adminlte.initialize === 'function') {
                    adminlte.initialize();
                }
            });
        });
        @endif
    })();
</script>
