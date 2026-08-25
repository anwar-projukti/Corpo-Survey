const STORAGE_KEY = 'theme';

function applyClass(resolved) {
    document.documentElement.classList.toggle('dark', resolved === 'dark');
}

/**
 * A logged-in user's preference is authoritative server-side and rendered
 * into the page (window.__themePreference, set in theme-init.blade.php).
 * A guest's choice only ever lives in localStorage. Reading the server
 * value first keeps this store consistent with the synchronous anti-flash
 * script that already ran in <head> before this module loaded.
 */
function initialPreference() {
    return window.__themePreference || localStorage.getItem(STORAGE_KEY) || 'system';
}

export default function themeStore() {
    return {
        // 'light' | 'dark' | 'system' — the user's stated preference.
        preference: initialPreference(),

        // Tracked reactively via the media query listener below, instead of
        // re-querying matchMedia() on every access to `resolved`.
        systemIsDark: window.matchMedia('(prefers-color-scheme: dark)').matches,

        init() {
            applyClass(this.resolved);

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
                this.systemIsDark = event.matches;

                if (this.preference === 'system') {
                    applyClass(this.resolved);
                }
            });
        },

        get resolved() {
            return this.preference === 'system' ? (this.systemIsDark ? 'dark' : 'light') : this.preference;
        },

        set(preference) {
            this.preference = preference;
            localStorage.setItem(STORAGE_KEY, preference);
            applyClass(this.resolved);

            if (window.__userIsAuthenticated) {
                fetch('/theme', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ theme: preference }),
                })
                    .then((response) => {
                        // fetch() only rejects on a network-layer failure —
                        // an HTTP error status (419 expired CSRF token, 409
                        // 2FA not yet confirmed, ...) resolves normally and
                        // must be checked explicitly, or it fails silently.
                        if (!response.ok) {
                            console.warn(`Theme preference did not sync to the server (HTTP ${response.status}).`);
                        }
                    })
                    .catch(() => {
                        // Best-effort: the local switch already happened: a
                        // failed sync just means it won't follow the user to
                        // another device until their next successful change.
                    });
            }
        },
    };
}
