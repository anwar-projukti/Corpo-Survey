import fieldApi from './api';
import offlineQueue from './offline-queue';
import fieldSync from './sync';

/**
 * Root Alpine component for the field user's "My Surveys" landing page
 * (spec §7 — "you've collected 23 responses today"). Also drives the
 * app-wide offline sync loop, since this is the page a field user lands
 * on between visits, where queued submissions are most likely to flush.
 */
export default function fieldSurveysIndex() {
    return {
        surveys: [],
        stats: null,
        loading: true,
        queueCount: 0,
        syncing: false,
        online: navigator.onLine,

        async init() {
            await Promise.all([this.loadSurveys(), this.loadStats(), this.refreshQueueCount()]);
            this.loading = false;

            window.addEventListener('online', () => {
                this.online = true;
                fieldSync.syncAll().then(() => this.afterSync());
            });
            window.addEventListener('offline', () => (this.online = false));

            fieldSync.onChange(() => {
                this.syncing = fieldSync.syncing;
                this.refreshQueueCount();
            });

            fieldSync.startAutoSync();
        },

        async loadSurveys() {
            this.surveys = await fieldApi.listSurveys();
        },

        async loadStats() {
            this.stats = await fieldApi.stats();
        },

        async refreshQueueCount() {
            this.queueCount = await offlineQueue.count();
        },

        async afterSync() {
            await Promise.all([this.loadStats(), this.refreshQueueCount()]);
        },

        async syncNow() {
            await fieldSync.syncAll();
            await this.afterSync();
        },
    };
}
