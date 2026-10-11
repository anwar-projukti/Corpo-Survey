import landingContentApi from './api';

/**
 * Public landing page content editor (Super Admin-only). `form` mirrors
 * every bilingual/plain field on the landing_contents table 1:1 — the
 * view's inputs bind to `form.<field>` directly rather than the component
 * exposing one named property per field, since there are ~40 of them.
 *
 * The logo uploads/removes immediately on selection rather than joining
 * the rest of `form` in the Save button's payload — it's a file, not a
 * validated() field the update endpoint accepts, so it always goes through
 * its own multipart request.
 */
export default function landingContentEditor() {
    return {
        form: {},
        logoUrl: null,
        loading: true,
        loadError: null,
        saving: false,
        saveError: null,
        savedAt: null,
        uploadingLogo: false,
        logoError: null,

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            this.loadError = null;

            try {
                const data = await landingContentApi.show();
                this.logoUrl = data.logo_url;
                delete data.logo_url;
                this.form = data;
            } catch {
                this.loadError = window.__i18n.load_failed;
            } finally {
                this.loading = false;
            }
        },

        async save() {
            this.saving = true;
            this.saveError = null;
            this.savedAt = null;

            try {
                const data = await landingContentApi.update(this.form);
                this.logoUrl = data.logo_url;
                delete data.logo_url;
                this.form = data;
                this.savedAt = new Date();
            } catch (error) {
                this.saveError = error?.response?.data?.message || window.__i18n.save_failed;
            } finally {
                this.saving = false;
            }
        },

        async uploadLogo(event) {
            const file = event.target.files[0];
            if (! file) {
                return;
            }

            this.uploadingLogo = true;
            this.logoError = null;

            try {
                const data = await landingContentApi.uploadLogo(file);
                this.logoUrl = data.logo_url;
            } catch (error) {
                this.logoError = error?.response?.data?.message || window.__i18n.logo_upload_failed;
            } finally {
                this.uploadingLogo = false;
                event.target.value = '';
            }
        },

        async removeLogo() {
            this.uploadingLogo = true;
            this.logoError = null;

            try {
                await landingContentApi.removeLogo();
                const data = await landingContentApi.show();
                this.logoUrl = data.logo_url;
            } catch {
                this.logoError = window.__i18n.logo_upload_failed;
            } finally {
                this.uploadingLogo = false;
            }
        },
    };
}
