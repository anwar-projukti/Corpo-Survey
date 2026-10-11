import userStatsApi from './user-stats-api';

/**
 * Org-wide field-user productivity report (spec §10.3) — "which user did
 * how many surveys," ranked by response_count (the backend already
 * orders the query this way).
 */
export default function userStats(organizationId) {
    return {
        organizationId,

        stats: [],
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
                const result = await userStatsApi.organizationStats(this.organizationId, { page: this.page });
                this.stats = result.data;
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
    };
}
