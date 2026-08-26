import Chart from 'chart.js/auto';
import analyticsApi from './api';
import surveysApi from '../surveys/api';

const CHART_COLORS = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1'];
const OPERATORS = ['equals', 'not_equals', 'gt', 'gte', 'lt', 'lte', 'contains', 'is_answered', 'is_not_answered'];
const OPERATORS_NO_VALUE = ['is_answered', 'is_not_answered'];

function emptyForm() {
    return {
        id: null,
        name: '',
        question_ids: [],
        filters: [],
        chart_types: {},
        date_from: '',
        date_to: '',
    };
}

/**
 * Custom report builder (spec §9) — a survey admin selects questions,
 * App\Support\ResponseFilterEngine filters, a chart type per question, and
 * an optional date range, saves that configuration for reuse
 * (App\Models\SavedReport), and re-runs it against live data
 * (App\Support\SavedReportRunner) without rebuilding it from scratch every
 * visit. A sibling of analyticsDashboard rather than folded into it — the
 * two are shown as separate sections on the same page but manage
 * independent state (the live dashboard's fixed metrics vs. a list of
 * saved, user-authored configurations).
 */
export default function reportBuilder(surveyId) {
    return {
        surveyId,
        questions: [],

        reports: [],
        loading: true,
        loadError: null,

        showForm: false,
        form: emptyForm(),
        saving: false,
        saveError: null,

        operators: OPERATORS,

        activeReportId: null,
        runResult: null,
        runLoading: false,
        runError: null,
        runCharts: {},

        async init() {
            const survey = await surveysApi.surveys.show(this.surveyId);
            const version = survey.current_published_version || survey.current_draft_version;
            this.questions = (version?.sections || []).flatMap((s) => s.questions);

            await this.loadReports();
        },

        async loadReports() {
            this.loading = true;
            this.loadError = null;

            try {
                this.reports = await analyticsApi.listReports(this.surveyId);
            } catch {
                this.loadError = window.__i18n.reports_load_failed;
            } finally {
                this.loading = false;
            }
        },

        needsValue(operator) {
            return !OPERATORS_NO_VALUE.includes(operator);
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

        // --- Builder form --------------------------------------------------

        openNewForm() {
            this.form = emptyForm();
            this.saveError = null;
            this.showForm = true;
        },

        openEditForm(report) {
            this.form = {
                id: report.id,
                name: report.name,
                question_ids: [...(report.config.question_ids || [])],
                filters: (report.config.filters || []).map((f) => ({ ...f })),
                chart_types: { ...(report.config.chart_types || {}) },
                date_from: report.config.date_from || '',
                date_to: report.config.date_to || '',
            };
            this.saveError = null;
            this.showForm = true;
        },

        closeForm() {
            this.showForm = false;
        },

        toggleQuestion(questionId) {
            const index = this.form.question_ids.indexOf(questionId);

            if (index === -1) {
                this.form.question_ids.push(questionId);
                this.form.chart_types[questionId] = this.form.chart_types[questionId] || 'doughnut';
            } else {
                this.form.question_ids.splice(index, 1);
                delete this.form.chart_types[questionId];
            }
        },

        addFilter() {
            this.form.filters.push({ question_id: this.questions[0]?.id ?? null, operator: 'equals', value: '' });
        },

        removeFilter(index) {
            this.form.filters.splice(index, 1);
        },

        async saveReport() {
            this.saving = true;
            this.saveError = null;

            const payload = {
                name: this.form.name,
                config: {
                    question_ids: this.form.question_ids,
                    filters: this.form.filters.filter((f) => f.question_id && f.operator),
                    chart_types: this.form.chart_types,
                    date_from: this.form.date_from || null,
                    date_to: this.form.date_to || null,
                },
            };

            try {
                if (this.form.id) {
                    await analyticsApi.updateReport(this.form.id, payload);
                } else {
                    await analyticsApi.createReport(this.surveyId, payload);
                }

                this.showForm = false;
                await this.loadReports();
            } catch (error) {
                this.saveError = error.response?.data?.message || window.__i18n.reports_save_failed;
            } finally {
                this.saving = false;
            }
        },

        async deleteReport(report) {
            if (!confirm(window.__i18n.reports_confirm_delete)) return;

            await analyticsApi.deleteReport(report.id);

            if (this.activeReportId === report.id) {
                this.closeRun();
            }

            await this.loadReports();
        },

        // --- Running a report ------------------------------------------------

        async runReport(report) {
            this.activeReportId = report.id;
            this.runResult = null;
            this.runError = null;
            this.runLoading = true;
            this.destroyRunCharts();

            try {
                this.runResult = await analyticsApi.runReport(report.id);
            } catch {
                if (this.activeReportId === report.id) this.runError = window.__i18n.reports_run_failed;
            } finally {
                if (this.activeReportId === report.id) this.runLoading = false;
            }
        },

        closeRun() {
            this.activeReportId = null;
            this.runResult = null;
            this.runError = null;
            this.destroyRunCharts();
        },

        destroyRunCharts() {
            Object.values(this.runCharts).forEach((chart) => chart.destroy());
            this.runCharts = {};
        },

        // Called via x-init on each result canvas as Alpine creates it
        // (see resources/views/analytics/show.blade.php) rather than
        // queried afterwards from a batch render pass — a querySelector
        // pass scheduled via $nextTick after runResult/runLoading change
        // could still run before Alpine had actually walked the nested
        // x-for's freshly-cloned template subtree, leaving Chart.js
        // constructed against a canvas stuck at its default 300x150 size.
        renderRunChart(canvas, question) {
            const labels = Object.keys(question.distribution);
            const counts = Object.values(question.distribution);

            this.runCharts[question.question_id] = new Chart(canvas, {
                type: question.chart_type === 'bar' ? 'bar' : 'doughnut',
                data: {
                    labels,
                    datasets: [{ data: counts, backgroundColor: labels.map((_, index) => CHART_COLORS[index % CHART_COLORS.length]) }],
                },
                options: {
                    responsive: true,
                    animation: false,
                    plugins: { legend: { display: labels.length <= 6, position: 'bottom' } },
                    scales: question.chart_type === 'bar' ? { y: { beginAtZero: true, ticks: { precision: 0 } } } : undefined,
                },
            });
        },
    };
}
