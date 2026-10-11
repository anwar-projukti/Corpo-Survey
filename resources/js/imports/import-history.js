import importsApi from './api';

/**
 * Import history list (spec §10.4's "import history" requirement) — shown
 * below the import wizard on the same page.
 */
export default function importHistory() {
    return {
        imports: [],
        meta: null,
        page: 1,
        loading: true,
        loadError: null,

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            this.loadError = null;

            try {
                const result = await importsApi.history({ page: this.page });
                this.imports = result.data;
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

        async refresh() {
            await this.load();
        },
    };
}
