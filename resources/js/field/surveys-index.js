import fieldApi from './api';
import offlineQueue from './offline-queue';
import fieldSync from './sync';
import reverseGeocode from '../support/reverse-geocode';

/**
 * Root Alpine component for the field user's "My Surveys" landing page
 * (spec §7 — "you've collected 23 responses today"). Also drives the
 * app-wide offline sync loop, since this is the page a field user lands
 * on between visits, where queued submissions are most likely to flush.
 *
 * A second tab, "My Responses", lets a field user review what they've
 * actually collected — the per-survey breakdown already in `stats`
 * doubles as a lightweight analysis view, and the response list/detail
 * are scoped to this user's own collected_by_user_id server-side
 * (App\Http\Controllers\Api\V1\My\MyResponseController).
 */
export default function fieldSurveysIndex() {
    return {
        activeTab: 'surveys',
        surveys: [],
        stats: null,
        loading: true,
        queueCount: 0,
        syncing: false,
        online: navigator.onLine,

        responses: [],
        responsesLoading: false,
        responsesLoaded: false,
        responsesPage: 1,
        responsesLastPage: 1,

        selectedResponse: null,
        responseDetailLoading: false,
        questionsBySurveyId: {},
        locationLabelsByResponseId: {},

        async init() {
            await this.loadInitialData();
            this.loading = false;

            window.addEventListener('online', () => this.recheckConnection());
            window.addEventListener('offline', () => (this.online = false));

            fieldSync.onChange(() => {
                this.syncing = fieldSync.syncing;
                this.refreshQueueCount();
            });

            fieldSync.startAutoSync();
        },

        async loadInitialData() {
            try {
                await Promise.all([this.loadSurveys(), this.loadStats(), this.refreshQueueCount()]);
                this.online = true;
            } catch (error) {
                if (this.isNetworkError(error)) this.online = false;
            }
        },

        isNetworkError(error) {
            return !error?.response;
        },

        // navigator.onLine only reflects whether the OS thinks a network
        // interface is up — it can get stuck `false` if the browser missed
        // firing its `online` event back after an earlier blip, even
        // though requests to our own server succeed fine. So this is a
        // real probe, not just trusting that flag: a successful reload of
        // this page's data proves we're online regardless of what
        // navigator.onLine says.
        async recheckConnection() {
            await this.loadInitialData();
            if (this.online) {
                await fieldSync.syncAll().then(() => this.afterSync());
            }
        },

        async switchTab(tab) {
            this.activeTab = tab;

            if (tab === 'responses' && ! this.responsesLoaded) {
                await this.loadResponses();
            }
        },

        async loadSurveys() {
            this.surveys = await fieldApi.listSurveys();
        },

        async loadStats() {
            this.stats = await fieldApi.stats();
        },

        responseCountFor(surveyId) {
            return this.stats?.by_survey?.find((row) => row.survey_id === surveyId)?.count ?? 0;
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

        async loadResponses(page = 1) {
            this.responsesLoading = true;

            try {
                const result = await fieldApi.listResponses(page);
                this.responses = result.data;
                this.responsesPage = result.meta?.current_page ?? 1;
                this.responsesLastPage = result.meta?.last_page ?? 1;
                this.responsesLoaded = true;
                this.responses.forEach((response) => this.resolveLocationLabel(response));
            } finally {
                this.responsesLoading = false;
            }
        },

        async resolveLocationLabel(response) {
            if (! this.hasLocation(response) || this.locationLabelsByResponseId[response.id]) {
                return;
            }

            const label = await reverseGeocode(response.latitude, response.longitude);
            if (label) {
                this.locationLabelsByResponseId[response.id] = label;
            }
        },

        async goToResponsesPage(page) {
            if (page < 1 || page > this.responsesLastPage) {
                return;
            }
            await this.loadResponses(page);
        },

        async openResponse(response) {
            this.selectedResponse = response;
            this.responseDetailLoading = true;

            try {
                const [detail] = await Promise.all([
                    fieldApi.showResponse(response.id),
                    this.loadQuestionsFor(response.survey_id),
                    this.resolveLocationLabel(response),
                ]);
                this.selectedResponse = detail;
            } finally {
                this.responseDetailLoading = false;
            }
        },

        async loadQuestionsFor(surveyId) {
            if (this.questionsBySurveyId[surveyId]) {
                return;
            }

            const survey = await fieldApi.showSurvey(surveyId);
            this.questionsBySurveyId[surveyId] = (survey.version?.sections || []).flatMap((s) => s.questions);
        },

        questionLabelFor(surveyId, questionId) {
            const question = (this.questionsBySurveyId[surveyId] || []).find((q) => q.id === questionId);
            return question?.label ?? `#${questionId}`;
        },

        closeResponse() {
            this.selectedResponse = null;
        },

        // --- Display helpers for started_at/submitted_at/latitude/longitude,
        // all of which have existed on survey_responses and in the API
        // response all along — field-form.js just never captured/sent
        // location until now, and this list never surfaced any of it. ---

        durationFor(response) {
            if (! response.started_at || ! response.submitted_at) {
                return null;
            }

            const seconds = Math.max(0, (new Date(response.submitted_at) - new Date(response.started_at)) / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);

            if (hours > 0) {
                return `${hours}h ${minutes % 60}m`;
            }
            if (minutes > 0) {
                return `${minutes}m ${Math.floor(seconds % 60)}s`;
            }
            return `${Math.floor(seconds)}s`;
        },

        hasLocation(response) {
            return response.latitude !== null && response.latitude !== undefined
                && response.longitude !== null && response.longitude !== undefined;
        },

        locationLabelFor(response) {
            if (! this.hasLocation(response)) {
                return null;
            }
            return this.locationLabelsByResponseId[response.id]
                || `${Number(response.latitude).toFixed(5)}, ${Number(response.longitude).toFixed(5)}`;
        },

        locationMapUrlFor(response) {
            return `https://www.openstreetmap.org/?mlat=${response.latitude}&mlon=${response.longitude}#map=16/${response.latitude}/${response.longitude}`;
        },
    };
}
