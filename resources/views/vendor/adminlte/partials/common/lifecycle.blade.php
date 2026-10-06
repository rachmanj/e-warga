<script>
    (() => {
        'use strict';

        window._Ewarga_Ready = (callback) => {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback, { once: true });
            } else {
                callback();
            }
        };

        window._Ewarga_Once = (key, callback) => {
            window._Ewarga_OnceKeys = window._Ewarga_OnceKeys || {};

            if (window._Ewarga_OnceKeys[key]) {
                return;
            }

            window._Ewarga_OnceKeys[key] = true;
            callback();
        };

        @if($spaNavigation)
        window._Ewarga_Once('spa-navigation', () => {
            document.addEventListener('livewire:navigated', () => {
                if (typeof adminlte !== 'undefined' && typeof adminlte.initialize === 'function') {
                    adminlte.initialize();
                }
            });
        });
        @endif
    })();
</script>
