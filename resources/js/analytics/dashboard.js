import Chart from 'chart.js/auto';
import analyticsApi from './api';

// Free-text types don't produce a meaningful distribution chart — the
// backend still returns an entry for them (an empty/near-empty bucket
// count), so it's filtered out here rather than asking the API to know
// about frontend rendering concerns.
const FREE_TEXT_TYPES = ['short_text', 'long_text', 'number', 'email', 'phone', 'url', 'date', 'time', 'date_time', 'address'];

const CHART_COLORS = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1'];

/**
 * Root Alpine component for the analytics dashboard (spec §9) — overview
 * metrics, response trend, per-question drop-off, and per-question answer
 * distributions. Scoped to one survey's version at a time, matching how
 * the backend itself is scoped (App\Http\Controllers\Concerns\ResolvesSurveyVersion).
 */
export default function analyticsDashboard(surveyId) {
    return {
        surveyId,
        data: null,
        loading: true,
        loadError: null,
        granularity: 'day',

        trendChart: null,
        questionCharts: {},

        // Bumped on every load() call so a $nextTick render callback from
        // a superseded (e.g. rapid granularity-switch) call can recognize
        // it's stale and skip rendering into canvases a newer load() may
        // already be about to tear down or has already rendered into.
        loadToken: 0,

        async init() {
            await this.load();

            this.$watch('granularity', () => this.load());
        },

        async load() {
            const token = ++this.loadToken;

            // Destroyed here, before `loading` flips true and Alpine's
            // x-if tears down the chart canvases out from under them —
            // otherwise each Chart.js instance's resize observer keeps
            // firing against a detached canvas and throws.
            this.destroyCharts();

            this.loading = true;
            this.loadError = null;

            try {
                const data = await analyticsApi.show(this.surveyId, { granularity: this.granularity });
                if (token !== this.loadToken) return;

                this.data = data;
                this.$nextTick(() => {
                    if (token === this.loadToken) this.renderCharts();
                });
            } catch {
                if (token === this.loadToken) this.loadError = window.__i18n.load_failed;
            } finally {
                if (token === this.loadToken) this.loading = false;
            }
        },

        destroyCharts() {
            this.trendChart?.destroy();
            this.trendChart = null;

            Object.values(this.questionCharts).forEach((chart) => chart.destroy());
            this.questionCharts = {};
        },

        get hasResponses() {
            return this.data && this.data.overview.total > 0;
        },

        get chartableQuestions() {
            if (!this.data) return [];

            return this.data.questions.filter((q) => !FREE_TEXT_TYPES.includes(q.type) && Object.keys(q.distribution).length > 0);
        },

        formatSeconds(seconds) {
            if (seconds === null || seconds === undefined) return window.__i18n.not_available;

            const minutes = Math.floor(seconds / 60);
            const remaining = Math.round(seconds % 60);

            return minutes > 0 ? `${minutes}m ${remaining}s` : `${remaining}s`;
        },

        renderCharts() {
            this.renderTrendChart();
            this.renderQuestionCharts();
        },

        renderTrendChart() {
            const canvas = this.$refs.trendCanvas;
            if (!canvas || !this.data) return;

            this.trendChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: this.data.trend.map((point) => point.period),
                    datasets: [
                        {
                            label: window.__i18n.response_trend,
                            data: this.data.trend.map((point) => point.count),
                            borderColor: CHART_COLORS[0],
                            backgroundColor: `${CHART_COLORS[0]}33`,
                            tension: 0.3,
                            fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    // Charts are torn down and rebuilt on every filter/
                    // granularity change; Chart.js's animation loop can
                    // still be mid-draw when destroy() runs and throws
                    // trying to draw into a released canvas context, so
                    // animation is disabled dashboard-wide rather than
                    // per-chart.
                    animation: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        },

        renderQuestionCharts() {
            this.chartableQuestions.forEach((question) => {
                const canvas = this.$el.querySelector(`.analytics-question-canvas[data-question-id="${question.question_id}"]`);
                if (!canvas) return;

                const labels = Object.keys(question.distribution);
                const counts = Object.values(question.distribution);

                this.questionCharts[question.question_id] = new Chart(canvas, {
                    type: labels.length > 6 ? 'bar' : 'doughnut',
                    data: {
                        labels,
                        datasets: [
                            {
                                data: counts,
                                backgroundColor: labels.map((_, index) => CHART_COLORS[index % CHART_COLORS.length]),
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        animation: false,
                        plugins: { legend: { display: labels.length <= 6, position: 'bottom' } },
                        scales: labels.length > 6 ? { y: { beginAtZero: true, ticks: { precision: 0 } } } : undefined,
                    },
                });
            });
        },
    };
}
