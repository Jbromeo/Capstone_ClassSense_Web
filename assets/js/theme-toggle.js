/* Shared Dark Mode toggle helper.
   Wires any #darkModeToggle checkbox (checked = DARK mode on), persists to
   the DB per account (api/theme.php, authenticated via the PHP session
   cookie) with an optimistic localStorage mirror ({uid, theme}) for
   logged-out pages and uid-scoped cross-tab sync. */
(function () {
    function currentUid() {
        return window.csThemeUid || null;
    }

    function saveLocal(isDark) {
        try {
            localStorage.setItem('cs_theme', JSON.stringify({ uid: currentUid(), theme: isDark ? 'dark' : 'light' }));
        } catch (e) {}
    }

    function persistServer(isDark) {
        fetch((window.CS_ROOT || '/ClassSense/') + 'api/theme.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ theme: isDark ? 'dark' : 'light' })
        }).then((res) => {
            if (!res.ok) console.warn('[theme] server persist failed:', res.status);
        }).catch((e) => console.warn('[theme] server persist failed:', e));
    }

    function applyTheme(isDark) {
        document.documentElement.classList.toggle('dark', isDark);
        window.csThemeIsLight = !isDark;
        saveLocal(isDark);
        persistServer(isDark);
    }

    function notify(isDark) {
        const msg = isDark ? 'Dark theme enabled' : 'Light theme enabled';
        if (window.showToast) showToast(msg, 'success');
        else if (window.showStatus) showStatus(msg, 'success');
    }

    function syncToggle() {
        const t = document.getElementById('darkModeToggle');
        if (!t) return;
        t.checked = document.documentElement.classList.contains('dark');
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
        if (e.key !== 'cs_theme' || !e.newValue) return;
        let theme = null;
        let srcUid = null;
        try {
            const parsed = JSON.parse(e.newValue);
            if (parsed && (parsed.theme === 'light' || parsed.theme === 'dark')) {
                theme = parsed.theme;
                srcUid = parsed.uid || null;
            }
        } catch (err) {
            if (e.newValue === 'light' || e.newValue === 'dark') {
                theme = e.newValue; // legacy plain-string value: treat as logged-out change
            }
        }
        if (!theme) return;
        // Only apply when the change belongs to this tab's account (or both
        // are logged out) — another account's toggle never leaks across.
        if (srcUid !== currentUid()) return;
        const isDark = theme === 'dark';
        document.documentElement.classList.toggle('dark', isDark);
        window.csThemeIsLight = !isDark;
        const t = document.getElementById('darkModeToggle');
        if (t) t.checked = isDark;
    });
})();
