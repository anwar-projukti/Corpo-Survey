/**
 * Drives the two-factor setup page (resources/views/two-factor-setup.blade.php)
 * against Fortify's own built-in endpoints (routes/web.php never defines
 * these — see vendor/laravel/fortify/routes/routes.php). Fortify enforces
 * a fresh password confirmation on every one of them via RequirePassword;
 * an axios request that hits that gate gets a clean 423 JSON response
 * (RequirePassword::handle — Accept header makes it $request->expectsJson()),
 * which this redirects to /user/confirm-password to resolve, coming back
 * here afterward.
 */
export default function twoFactorSetup() {
    return {
        step: 'loading', // 'loading' | 'enable' | 'confirm' | 'recovery' | 'done'
        qrCodeSvg: null,
        manualSetupUrl: null,
        confirmationCode: '',
        recoveryCodes: [],
        error: null,
        working: false,

        /**
         * A confirmation code isn't entered in one uninterrupted request —
         * enable() can bounce the browser away to /user/confirm-password
         * first (handleError()'s 423 branch) and back again, a full page
         * reload that resets all Alpine state. Rather than track that
         * round trip explicitly, just ask Fortify's own qr-code endpoint
         * whether a secret already exists: it returns [] pre-enable
         * (TwoFactorQrCodeController::show) and real svg/url data once
         * enable() has already run, which is exactly the signal needed to
         * resume at the confirm step instead of silently landing back on
         * a button that looks unclicked.
         */
        async init() {
            this.working = true;

            try {
                await this.loadQrCode();
                this.step = this.qrCodeSvg ? 'confirm' : 'enable';
            } catch {
                this.step = 'enable';
            } finally {
                this.working = false;
            }
        },

        async enable() {
            this.working = true;
            this.error = null;

            try {
                await axios.post('/user/two-factor-authentication');
                await this.loadQrCode();
                this.step = 'confirm';
            } catch (error) {
                this.handleError(error);
            } finally {
                this.working = false;
            }
        },

        async loadQrCode() {
            const { data } = await axios.get('/user/two-factor-qr-code');
            this.qrCodeSvg = data.svg;
            this.manualSetupUrl = data.url;
        },

        async confirm() {
            this.working = true;
            this.error = null;

            try {
                await axios.post('/user/confirmed-two-factor-authentication', { code: this.confirmationCode });
                await this.loadRecoveryCodes();
                this.step = 'recovery';
            } catch (error) {
                if (error?.response?.status === 422) {
                    this.error = window.__i18n.invalid_code;
                } else {
                    this.handleError(error);
                }
            } finally {
                this.working = false;
            }
        },

        async loadRecoveryCodes() {
            const { data } = await axios.get('/user/two-factor-recovery-codes');
            this.recoveryCodes = data;
        },

        finish() {
            window.location.href = window.__i18n.dashboard_url;
        },

        handleError(error) {
            if (error?.response?.status === 423) {
                window.location.href = window.__i18n.confirm_password_url;
                return;
            }

            this.error = window.__i18n.generic_error;
        },
    };
}
