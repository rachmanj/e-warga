<li class="nav-item">
    <button type="button" class="nav-link btn btn-link border-0 px-2" id="ewarga-color-mode-toggle"
            aria-label="Aktifkan mode gelap">
        <i class="bi bi-moon-fill" data-ewarga-theme-icon="light" aria-hidden="true"></i>
        <i class="bi bi-sun-fill d-none" data-ewarga-theme-icon="dark" aria-hidden="true"></i>
    </button>
</li>

@once
    @push('js')
        <script>
            window._Ewarga_Ready(() => {
                const STORAGE_KEY = 'lte-theme';
                const root = document.documentElement;
                const button = document.getElementById('ewarga-color-mode-toggle');
                if (!button) {
                    return;
                }

                const iconLight = button.querySelector('[data-ewarga-theme-icon="light"]');
                const iconDark = button.querySelector('[data-ewarga-theme-icon="dark"]');

                const syncToggleUi = () => {
                    const isDark = root.getAttribute('data-bs-theme') === 'dark';
                    iconLight.classList.toggle('d-none', isDark);
                    iconDark.classList.toggle('d-none', !isDark);
                    button.setAttribute(
                        'aria-label',
                        isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap',
                    );
                };

                syncToggleUi();

                button.addEventListener('click', () => {
                    const isDark = root.getAttribute('data-bs-theme') === 'dark';
                    const next = isDark ? 'light' : 'dark';
                    root.setAttribute('data-bs-theme', next);
                    root.style.colorScheme = next;
                    try {
                        localStorage.setItem(STORAGE_KEY, next);
                    } catch (e) {
                        // localStorage may be unavailable.
                    }
                    syncToggleUi();
                });
            });
        </script>
    @endpush
@endonce
