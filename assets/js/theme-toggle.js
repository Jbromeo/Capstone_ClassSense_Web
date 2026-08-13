/* Shared Dark Mode toggle helper.
   Wires any #darkModeToggle checkbox, persists to localStorage (cs_theme),
   and keeps open tabs in sync via the storage event. */
(function () {
    function applyTheme(isLight) {
        document.documentElement.classList.toggle('dark', !isLight);
        window.csThemeIsLight = isLight;
        try { localStorage.setItem('cs_theme', isLight ? 'light' : 'dark'); } catch (e) {}
    }

    function notify(isLight) {
        const msg = isLight ? 'Light theme enabled' : 'Dark theme enabled';
        if (window.showToast) showToast(msg, 'success');
        else if (window.showStatus) showStatus(msg, 'success');
    }

    function syncToggle() {
        const t = document.getElementById('darkModeToggle');
        if (!t) return;
        t.checked = !document.documentElement.classList.contains('dark');
        t.addEventListener('change', () => {
            applyTheme(t.checked);
            notify(t.checked);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncToggle);
    } else {
        syncToggle();
    }

    window.addEventListener('storage', (e) => {
        if (e.key !== 'cs_theme') return;
        const isLight = e.newValue === 'light';
        document.documentElement.classList.toggle('dark', !isLight);
        window.csThemeIsLight = isLight;
        const t = document.getElementById('darkModeToggle');
        if (t) t.checked = isLight;
    });
})();
