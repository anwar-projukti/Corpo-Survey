import publicApi from './public-api';
import RuleEngine from './rule-engine';

const AUTOSAVE_STORAGE_PREFIX = 'survey_progress_';
const AUTOSAVE_DEBOUNCE_MS = 600;

/**
 * Root Alpine component for the respondent-facing public survey page
 * (resources/views/surveys/public.blade.php). Supports both display modes
 * from spec §3.2/§5.8 (one_question_per_screen vs. every question on one
 * scrolling page) driven by the version's own settings — the same
 * component and answer state back both, only the template differs.
 *
 * Everything this component decides (which questions are visible/required,
 * whether the survey should end early) is a UX convenience derived from
 * RuleEngine — App\Actions\Surveys\SubmitSurveyResponse re-derives all of
 * it server-side and is the actual source of truth.
 */
export default function publicSurveyForm(surveyId) {
    return {
        surveyId,
        survey: null,
        loading: true,
        loadError: null,
        notFoundOrClosed: false,
        alreadyResponded: false,

        answers: {},
        errors: {},
        currentIndex: 0,
        submitting: false,
        submitError: null,
        submitted: false,
        submittedStatus: null,

        otpDestination: '',
        otpCode: '',
        otpSent: false,
        otpVerified: false,
        otpError: null,
        otpSending: false,
        otpVerifying: false,

        captchaToken: null,
        startedAt: null,

        async init() {
            await this.load();
            this.$watch('answers', () => this.scheduleAutosave(), { deep: true });
        },

        async load() {
            this.loading = true;
            this.loadError = null;

            try {
                this.survey = await publicApi.show(this.surveyId);
                this.restoreProgress();
                this.startedAt = this.startedAt || new Date().toISOString();
            } catch (error) {
                const status = error?.response?.status;
                if (status === 404 || status === 409) {
                    this.notFoundOrClosed = status === 404;
                    this.alreadyResponded = status === 409;
                } else {
                    this.loadError = window.__i18n.validation_error;
                }
            } finally {
                this.loading = false;
            }
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

        // --- Autosave --------------------------------------------------

        get storageKey() {
            return `${AUTOSAVE_STORAGE_PREFIX}${this.surveyId}`;
        },

        scheduleAutosave() {
            if (!this.settings.allow_save_and_resume) return;

            clearTimeout(this._autosaveTimer);
            this._autosaveTimer = setTimeout(() => this.saveProgress(), AUTOSAVE_DEBOUNCE_MS);
        },

        saveProgress() {
            try {
                localStorage.setItem(
                    this.storageKey,
                    JSON.stringify({ answers: this.answers, currentIndex: this.currentIndex, startedAt: this.startedAt }),
                );
            } catch {
                // Storage can be unavailable (private browsing, quota) — losing
                // autosave is a degraded UX, not a failure worth surfacing.
            }
        },

        restoreProgress() {
            if (!this.settings.allow_save_and_resume) return;

            try {
                const raw = localStorage.getItem(this.storageKey);
                if (!raw) return;

                const saved = JSON.parse(raw);
                this.answers = saved.answers || {};
                this.currentIndex = saved.currentIndex || 0;
                this.startedAt = saved.startedAt || null;
            } catch {
                // A corrupt/foreign value in storage just means no progress to restore.
            }
        },

        clearProgress() {
            try {
                localStorage.removeItem(this.storageKey);
            } catch {
                // Nothing to clean up if storage isn't available.
            }
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

        async submit() {
            if (this.settings.require_otp && !this.otpVerified) {
                this.submitError = window.__i18n.invalid_code;
                return;
            }

            if (!this.validateAll()) {
                this.submitError = window.__i18n.validation_error;
                return;
            }

            this.submitting = true;
            this.submitError = null;

            try {
                const result = await publicApi.submit(this.surveyId, {
                    answers: this.answers,
                    otp_destination: this.settings.require_otp ? this.otpDestination : undefined,
                    captcha_token: this.captchaToken,
                    started_at: this.startedAt,
                });

                this.submitted = true;
                this.submittedStatus = result.status;
                this.clearProgress();
            } catch (error) {
                const status = error?.response?.status;
                if (status === 409) {
                    this.alreadyResponded = true;
                } else if (status === 422 && error.response.data?.errors) {
                    this.applyServerErrors(error.response.data.errors);
                    this.submitError = window.__i18n.validation_error;
                } else if (status === 429) {
                    this.submitError = window.__i18n.rate_limited;
                } else {
                    this.submitError = window.__i18n.validation_error;
                }
            } finally {
                this.submitting = false;
            }
        },

        applyServerErrors(errors) {
            for (const [key, messages] of Object.entries(errors)) {
                const match = key.match(/^answers\.(\d+)$/);
                if (match) this.errors[match[1]] = Array.isArray(messages) ? messages[0] : messages;
            }
        },

        // --- OTP ------------------------------------------------------

        async sendOtp() {
            if (!this.otpDestination) return;

            this.otpSending = true;
            this.otpError = null;

            try {
                await publicApi.requestOtp(this.surveyId, this.otpDestination, this.captchaToken);
                this.otpSent = true;
            } catch {
                this.otpError = window.__i18n.invalid_code;
            } finally {
                this.otpSending = false;
            }
        },

        async verifyOtp() {
            if (!this.otpCode) return;

            this.otpVerifying = true;
            this.otpError = null;

            try {
                await publicApi.verifyOtp(this.surveyId, this.otpDestination, this.otpCode);
                this.otpVerified = true;
            } catch {
                this.otpError = window.__i18n.invalid_code;
            } finally {
                this.otpVerifying = false;
            }
        },
    };
}
