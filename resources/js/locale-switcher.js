/**
 * Submits the locale change via fetch() instead of a plain <form> POST, so
 * the switch doesn't fire while the button is mid-animation and the button
 * can show a disabled/pending state instead of the browser's native
 * navigation-in-progress UI (favicon spinner, page going inert) — the part
 * that reads as "shaking" next to the theme toggle's instant switch.
 *
 * A real reload still follows: nearly every string on the page is rendered
 * server-side via __(), so there is no practical way to re-translate the
 * page in place without a soft-navigation framework this app doesn't use.
 */
export default function localeSwitcher() {
    return {
        pending: false,

        submit(event) {
            if (this.pending) {
                return;
            }

            this.pending = true;

            const form = event.target;
            const data = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: data,
            })
                .then((response) => {
                    if (response.ok || response.redirected) {
                        window.location.reload();
                        return;
                    }

                    console.warn(`Language preference did not save (HTTP ${response.status}).`);
                    this.pending = false;
                })
                .catch(() => {
                    // No network response — fall back to a normal form
                    // submit so the switch still works.
                    form.submit();
                });
        },
    };
}
