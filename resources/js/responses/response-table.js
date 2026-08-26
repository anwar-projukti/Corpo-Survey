import responsesApi from './api';
import surveysApi from '../surveys/api';

const OPERATORS = ['equals', 'not_equals', 'gt', 'gte', 'lt', 'lte', 'contains', 'is_answered', 'is_not_answered'];
const OPERATORS_NO_VALUE = ['is_answered', 'is_not_answered'];

/**
 * Root Alpine component for the advanced response table (spec §8.1) —
 * search/filter/sort/pagination, column visibility, bulk selection and
 * bulk review actions. Scoped to one survey's current version at a time,
 * matching how the backend itself is scoped (App\Http\Controllers\Api\V1\Responses\SurveyResponseController).
 */
export default function responseTable(surveyId) {
    return {
        surveyId,
        survey: null,
        questions: [],

        responses: [],
        meta: null,
        loading: true,
        loadError: null,

        page: 1,
        sort: '-submitted_at',
        filters: [],
        visibleColumns: {},

        selected: new Set(),
        bulkWorking: false,

        async init() {
            await this.loadSurvey();
            await this.loadResponses();
        },

        async loadSurvey() {
            this.survey = await surveysApi.surveys.show(this.surveyId);
            const version = this.survey.current_published_version || this.survey.current_draft_version;
            this.questions = (version?.sections || []).flatMap((s) => s.questions);

            this.visibleColumns = Object.fromEntries(this.questions.slice(0, 5).map((q) => [q.id, true]));
        },

        async loadResponses() {
            this.loading = true;
            this.loadError = null;
            this.selected = new Set();

            try {
                const result = await responsesApi.list(this.surveyId, {
                    page: this.page,
                    sort: this.sort,
                    filters: this.appliedFilters(),
                });
                this.responses = result.data;
                this.meta = result.meta;
            } catch {
                this.loadError = window.__i18n.load_failed;
            } finally {
                this.loading = false;
            }
        },

        appliedFilters() {
            return this.filters
                .filter((f) => f.question_id && f.operator)
                .map((f) => (OPERATORS_NO_VALUE.includes(f.operator) ? { question_id: f.question_id, operator: f.operator } : f));
        },

        // --- Filters -----------------------------------------------------

        addFilter() {
            this.filters.push({ question_id: this.questions[0]?.id ?? null, operator: 'equals', value: '' });
        },

        removeFilter(index) {
            this.filters.splice(index, 1);
        },

        needsValue(operator) {
            return !OPERATORS_NO_VALUE.includes(operator);
        },

        get operators() {
            return OPERATORS;
        },

        get operatorLabels() {
            return {
                equals: window.__i18n.operator_equals,
                not_equals: window.__i18n.operator_not_equals,
                gt: window.__i18n.operator_gt,
                gte: window.__i18n.operator_gte,
                lt: window.__i18n.operator_lt,
                lte: window.__i18n.operator_lte,
                contains: window.__i18n.operator_contains,
                is_answered: window.__i18n.operator_is_answered,
                is_not_answered: window.__i18n.operator_is_not_answered,
            };
        },

        async applyFilters() {
            this.page = 1;
            await this.loadResponses();
        },

        async clearFilters() {
            this.filters = [];
            this.page = 1;
            await this.loadResponses();
        },

        // --- Sorting / pagination ------------------------------------------

        async sortBy(column) {
            this.sort = this.sort === column ? `-${column}` : column;
            await this.loadResponses();
        },

        async goToPage(page) {
            if (page < 1 || (this.meta && page > this.meta.last_page)) return;
            this.page = page;
            await this.loadResponses();
        },

        // --- Column visibility -----------------------------------------

        toggleColumn(questionId) {
            this.visibleColumns[questionId] = !this.visibleColumns[questionId];
        },

        answerFor(response, questionId) {
            const answer = (response.answers || []).find((a) => a.survey_question_id === questionId);
            return answer ? answer.value : null;
        },

        formatAnswer(value) {
            if (value === null || value === undefined || value === '') return '—';
            if (Array.isArray(value)) return value.join(', ');
            if (typeof value === 'object') return Object.entries(value).map(([k, v]) => `${k}: ${v}`).join(', ');
            return String(value);
        },

        // --- Selection / bulk actions ------------------------------------

        toggleSelect(id) {
            this.selected.has(id) ? this.selected.delete(id) : this.selected.add(id);
            this.selected = new Set(this.selected);
        },

        toggleSelectAll() {
            this.selected = this.selected.size === this.responses.length ? new Set() : new Set(this.responses.map((r) => r.id));
        },

        async bulkReview(reviewStatus) {
            this.bulkWorking = true;
            try {
                await Promise.all([...this.selected].map((id) => responsesApi.review(id, reviewStatus)));
                await this.loadResponses();
            } finally {
                this.bulkWorking = false;
            }
        },

        async bulkDelete() {
            if (!confirm(window.__i18n.confirm_bulk_delete)) return;

            this.bulkWorking = true;
            try {
                await Promise.all([...this.selected].map((id) => responsesApi.destroy(this.surveyId, id)));
                await this.loadResponses();
            } finally {
                this.bulkWorking = false;
            }
        },
    };
}
