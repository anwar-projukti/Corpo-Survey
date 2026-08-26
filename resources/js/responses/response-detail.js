import responsesApi from './api';
import surveysApi from '../surveys/api';

/**
 * Root Alpine component for the response detail view (spec §8, §5.11) —
 * shows every answer with an inline edit affordance (every save is
 * audited server-side by App\Actions\Responses\EditResponseAnswer),
 * review status (approve/reject/archive), and a notes thread.
 */
export default function responseDetail(surveyId, responseId) {
    return {
        surveyId,
        responseId,
        survey: null,
        response: null,
        questions: [],
        loading: true,
        loadError: null,

        editingAnswerId: null,
        editValue: '',
        savingAnswer: false,
        answerError: null,

        reviewWorking: false,
        newNote: '',
        addingNote: false,

        async init() {
            await Promise.all([this.loadSurvey(), this.loadResponse()]);
        },

        async loadSurvey() {
            this.survey = await surveysApi.surveys.show(this.surveyId);
            const version = this.survey.current_published_version || this.survey.current_draft_version;
            this.questions = (version?.sections || []).flatMap((s) => s.questions);
        },

        async loadResponse() {
            this.loading = true;
            this.loadError = null;
            try {
                this.response = await responsesApi.show(this.surveyId, this.responseId);
            } catch {
                this.loadError = window.__i18n.load_failed;
            } finally {
                this.loading = false;
            }
        },

        questionFor(questionId) {
            return this.questions.find((q) => q.id === questionId);
        },

        answerFor(questionId) {
            return (this.response?.answers || []).find((a) => a.survey_question_id === questionId) || null;
        },

        formatAnswer(value) {
            if (value === null || value === undefined || value === '') return window.__i18n.no_answer;
            if (Array.isArray(value)) return value.join(', ');
            if (typeof value === 'object') return Object.entries(value).map(([k, v]) => `${k}: ${v}`).join(', ');
            return String(value);
        },

        startEdit(answer, questionId) {
            this.editingAnswerId = answer ? answer.id : `new-${questionId}`;
            this.editValue = answer ? answer.value : '';
            this.answerError = null;
        },

        cancelEdit() {
            this.editingAnswerId = null;
            this.answerError = null;
        },

        async saveAnswer(answer) {
            if (!answer) return;

            this.savingAnswer = true;
            this.answerError = null;

            try {
                const updated = await responsesApi.updateAnswer(answer.id, this.editValue);
                const index = this.response.answers.findIndex((a) => a.id === answer.id);
                this.response.answers[index] = updated;
                this.editingAnswerId = null;
            } catch (error) {
                this.answerError = error?.response?.data?.errors?.value?.[0] || window.__i18n.save_failed;
            } finally {
                this.savingAnswer = false;
            }
        },

        async review(status) {
            this.reviewWorking = true;
            try {
                this.response = { ...this.response, ...(await responsesApi.review(this.responseId, status)) };
            } finally {
                this.reviewWorking = false;
            }
        },

        async addNote() {
            if (!this.newNote.trim()) return;

            this.addingNote = true;
            try {
                const note = await responsesApi.addNote(this.responseId, this.newNote);
                this.response.notes = [note, ...(this.response.notes || [])];
                this.newNote = '';
            } finally {
                this.addingNote = false;
            }
        },
    };
}
