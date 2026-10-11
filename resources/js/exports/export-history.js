import exportsApi from './api';

/**
 * Export history page (spec §13's "Export History" nav item) — lists
 * every export the org has requested, and lets an Org Admin kick off a
 * fresh organization-wide export directly from here. Per-survey exports
 * are started from that survey's own response table (see
 * resources/js/responses/export-panel.js), not from this page.
 */
export default function exportHistory(organizationId) {
    return {
        organizationId,

        exports: [],
        meta: null,
        page: 1,
        loading: true,
        loadError: null,

        starting: false,
        startError: null,
        format: 'xlsx',

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            this.loadError = null;

            try {
                const result = await exportsApi.history({ page: this.page });
                this.exports = result.data;
                this.meta = result.meta;
            } catch {
                this.loadError = window.__i18n.load_failed;
            } finally {
                this.loading = false;
            }
        },

        async goToPage(page) {
            if (page < 1 || (this.meta && page > this.meta.last_page)) return;
            this.page = page;
            await this.load();
        },

        async startOrganizationExport() {
            this.starting = true;
            this.startError = null;

            try {
                await exportsApi.exportOrganization(this.organizationId, { format: this.format });
                this.page = 1;
                await this.load();
            } catch (error) {
                this.startError = error?.response?.data?.message || window.__i18n.start_failed;
            } finally {
                this.starting = false;
            }
        },
    };
}
