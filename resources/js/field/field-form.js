import fieldApi from './api';
import offlineQueue from './offline-queue';
import fieldSync from './sync';
import RuleEngine from '../surveys/rule-engine';
import reverseGeocode from '../support/reverse-geocode';

/**
 * Root Alpine component for the door-to-door field collection form
 * (resources/views/field/survey-form.blade.php) — spec §7. Shares its
 * question-rendering partial and rule-driven visibility/required logic
 * with the public respondent form (resources/js/surveys/public-form.js),
 * but the submission path is different in two ways: it's authenticated
 * (no CAPTCHA/OTP/respondent-cookie duplicate-policy — none of that
 * applies to a logged-in field user), and it degrades to an offline queue
 * (resources/js/field/offline-queue.js) when the network request fails,
 * rather than surfacing an error the field user can't act on mid-visit.
 */
export default function fieldSurveyForm(surveyId) {
    return {
        surveyId,
        survey: null,
        loading: true,
        loadError: null,

        answers: {},
        errors: {},
        currentIndex: 0,
        submitting: false,
        submitError: null,
        submitted: false,
        queuedOffline: false,
        startedAt: null,

        clientSubmissionId: null,
        queueCount: 0,
        online: navigator.onLine,
        latitude: null,
        longitude: null,
        locationLabel: null,

        async init() {
            this.clientSubmissionId = crypto.randomUUID();
            await this.load();
            await this.refreshQueueCount();
            this.captureLocation();

            window.addEventListener('online', () => this.recheckConnection());
            window.addEventListener('offline', () => (this.online = false));

            fieldSync.onChange(() => this.refreshQueueCount());
        },

        // navigator.onLine only reflects whether the OS thinks a network
        // interface is up — it can get stuck `false` if the browser missed
        // firing its `online` event back after a earlier blip (a known
        // browser quirk), even though requests to our own server succeed
        // fine. So this is a real probe, not just trusting that flag: a
        // successful request proves we're online regardless of what
        // navigator.onLine says, and only a genuine network-level failure
        // (isNetworkError — no response at all) flips the banner back on.
        async recheckConnection() {
            try {
                await fieldApi.showSurvey(this.surveyId);
                this.online = true;
                await fieldSync.syncAll();
                await this.refreshQueueCount();
            } catch (error) {
                if (this.isNetworkError(error)) this.online = false;
            }
        },

        // Best-effort only — a field visit happens wherever the respondent
        // is, often indoors or with a weak signal, so a slow/denied/failed
        // geolocation read must never block or delay filling out the
        // survey. latitude/longitude simply stay null and the response
        // submits without them, same as before this existed.
        captureLocation() {
            if (! navigator.geolocation) {
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    this.latitude = position.coords.latitude;
                    this.longitude = position.coords.longitude;
                    this.locationLabel = await reverseGeocode(this.latitude, this.longitude);
                },
                () => {},
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 },
            );
        },

        async load() {
            this.loading = true;
            this.loadError = null;

            try {
                this.survey = await fieldApi.showSurvey(this.surveyId);
                this.startedAt = new Date().toISOString();
                this.online = true;
            } catch (error) {
                this.loadError = window.__i18n.load_failed;
                if (this.isNetworkError(error)) this.online = false;
            } finally {
                this.loading = false;
            }
        },

        async refreshQueueCount() {
            this.queueCount = await offlineQueue.forSurvey(Number(this.surveyId)).then((items) => items.length);
        },

        get settings() {
            return this.survey?.version?.settings || {};
        },

        get engine() {
            return new RuleEngine(this.survey.version.sections, this.survey.version.rules, this.answers);
        },

        get visibleQuestions() {
            return this.engine.visibleQuestions();
        },

        get requiredQuestionIds() {
            return this.engine.requiredQuestionIds();
        },

        get isOnePerScreen() {
            return !!this.settings.one_question_per_screen;
        },

        get currentQuestion() {
            return this.isOnePerScreen ? this.visibleQuestions[this.currentIndex] : null;
        },

        get progress() {
            const total = this.visibleQuestions.length;
            const current = Math.min(this.currentIndex + 1, total);
            return { current, total, percent: total ? Math.round((current / total) * 100) : 0 };
        },

        isRequired(question) {
            return this.requiredQuestionIds.has(question.id);
        },

        isBlank(value) {
            return value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0);
        },

        // --- Navigation (one-question-per-screen mode) ---------------------

        canGoNext() {
            const question = this.currentQuestion;
            if (!question) return true;

            if (this.isRequired(question) && this.isBlank(this.answers[question.id])) {
                this.errors[question.id] = window.__i18n.validation_error;
                return false;
            }

            delete this.errors[question.id];
            return true;
        },

        next() {
            if (!this.canGoNext()) return;
            if (this.currentIndex < this.visibleQuestions.length - 1) {
                this.currentIndex++;
            }
        },

        previous() {
            if (this.currentIndex > 0) this.currentIndex--;
        },

        get isLastQuestion() {
            return this.currentIndex >= this.visibleQuestions.length - 1;
        },

        // --- Submission --------------------------------------------------

        validateAll() {
            this.errors = {};

            for (const id of this.requiredQuestionIds) {
                if (this.isBlank(this.answers[id])) {
                    this.errors[id] = window.__i18n.validation_error;
                }
            }

            return Object.keys(this.errors).length === 0;
        },

        buildPayload() {
            // this.answers is an Alpine-reactive Proxy — IndexedDB's
            // structured-clone algorithm can't store a Proxy directly
            // (DataCloneError, thrown synchronously from offlineQueue's
            // put()). A JSON round-trip strips the reactivity wrapper down
            // to a plain object, which also happens to be exactly what
            // axios would have serialized onto the wire anyway, so the
            // queued payload and a live-submitted one are byte-identical.
            return JSON.parse(
                JSON.stringify({
                    answers: this.answers,
                    started_at: this.startedAt,
                    client_submission_id: this.clientSubmissionId,
                    latitude: this.latitude,
                    longitude: this.longitude,
                }),
            );
        },

        async submit() {
            if (!this.validateAll()) {
                this.submitError = window.__i18n.validation_error;
                return;
            }

            this.submitting = true;
            this.submitError = null;

            const payload = this.buildPayload();

            try {
                await axios.post(`/api/v1/my/surveys/${this.surveyId}/responses`, payload);
                this.submitted = true;
            } catch (error) {
                if (this.isNetworkError(error)) {
                    // Queueing itself must never fail silently — losing a
                    // field agent's real-world visit with no indication is
                    // strictly worse than a visible error they can retry
                    // or escalate, since a silent "success" looks the same
                    // as a real one and the data is simply gone.
                    try {
                        await offlineQueue.enqueue(Number(this.surveyId), payload);
                        this.submitted = true;
                        this.queuedOffline = true;
                        await this.refreshQueueCount();
                    } catch {
                        this.submitError = window.__i18n.queue_failed;
                    }
                } else if (error?.response?.status === 422 && error.response.data?.errors) {
                    this.applyServerErrors(error.response.data.errors);
                    this.submitError = window.__i18n.validation_error;
                } else {
                    this.submitError = window.__i18n.generic_error;
                }
            } finally {
                this.submitting = false;
            }
        },

        isNetworkError(error) {
            return !error?.response;
        },

        applyServerErrors(errors) {
            for (const [key, messages] of Object.entries(errors)) {
                const match = key.match(/^answers\.(\d+)$/);
                if (match) this.errors[match[1]] = Array.isArray(messages) ? messages[0] : messages;
            }
        },

        startAnother() {
            this.answers = {};
            this.errors = {};
            this.currentIndex = 0;
            this.submitted = false;
            this.queuedOffline = false;
            this.submitError = null;
            this.startedAt = new Date().toISOString();
            this.clientSubmissionId = crypto.randomUUID();
            this.latitude = null;
            this.longitude = null;
            this.locationLabel = null;
            this.captureLocation();
        },
    };
}
