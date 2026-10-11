import api from './api';

/**
 * Minimal valid config for a freshly-created question of the given type.
 * Only matrix has a hard-required config shape server-side
 * (QuestionConfigData::rulesForType — matrixRows/matrixColumns are
 * `required`, `array`, `min:1`, each entry a non-empty `string`); every
 * other type is fine starting from {}. Kept in sync with that validation,
 * not derived from it, since it only needs to cover the one type that
 * would otherwise 422 immediately on creation.
 *
 * Row/column entries can't start as '' — Laravel's ConvertEmptyStringsToNull
 * middleware turns any empty string in the request body into null before
 * validation runs, which would fail the 'string' rule.
 */
function defaultConfigFor(type) {
    if (type === 'matrix') {
        return { matrixRows: [window.__i18n.untitled_row], matrixColumns: [window.__i18n.untitled_column] };
    }

    return {};
}

/**
 * Root Alpine component for the survey builder page (resources/views/surveys/builder.blade.php).
 * Owns the whole survey tree in memory and keeps it in sync with the API —
 * every mutation is optimistic-free: it waits for the server response
 * before updating local state, since section/question/option ids only
 * exist once the server has created the row.
 */
export default function surveyBuilder(surveyId, canEdit) {
    return {
        surveyId,
        canEdit,
        survey: null,
        version: null, // the version currently being edited (always the draft) or viewed (published, read-only)
        mode: 'draft', // 'draft' | 'published' — which version's tree is shown
        loading: true,
        saving: false,
        saveError: null,
        activeQuestionId: null,
        showRules: false,

        // Shared delete confirmation modal — see resources/views/organizations/
        // index.blade.php for the same pattern (askConfirm/runConfirm/cancelConfirm).
        confirmOpen: false,
        confirmTitle: '',
        confirmBody: '',
        confirmVariant: 'danger',
        confirmBusy: false,
        confirmAction: null,

        askConfirm({ title, body, onConfirm, variant = 'danger' }) {
            this.confirmTitle = title;
            this.confirmBody = body;
            this.confirmVariant = variant;
            this.confirmAction = onConfirm;
            this.confirmOpen = true;
        },

        async runConfirm() {
            if (!this.confirmAction) return;
            this.confirmBusy = true;
            try {
                await this.confirmAction();
                this.confirmOpen = false;
            } finally {
                this.confirmBusy = false;
                this.confirmAction = null;
            }
        },

        cancelConfirm() {
            this.confirmOpen = false;
            this.confirmAction = null;
        },

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            this.survey = await api.surveys.show(this.surveyId);
            this.mode = this.survey.current_draft_version ? 'draft' : 'published';
            this.version = this.mode === 'draft' ? this.survey.current_draft_version : this.survey.current_published_version;
            this.loading = false;
        },

        viewVersion(mode) {
            this.mode = mode;
            this.version = mode === 'draft' ? this.survey.current_draft_version : this.survey.current_published_version;
        },

        // canEdit reflects SurveyPolicy::update (survey.edit) — folded in here
        // rather than checked separately by every consumer (section-card,
        // rules-panel, question-config-panel, rule-editor, question-row,
        // question-type-picker all key off this one getter) so a Viewer
        // (survey.view without survey.edit) can't type into/reorder/delete
        // anything just because a draft happens to be open.
        get isDraftEditable() {
            return this.canEdit && this.mode === 'draft' && this.version && this.version.status === 'draft';
        },

        async withSaving(fn) {
            this.saving = true;
            this.saveError = null;
            try {
                return await fn();
            } catch (error) {
                this.saveError = error?.response?.data?.message || window.__i18n.save_failed;
                throw error;
            } finally {
                this.saving = false;
            }
        },

        // --- Sections -------------------------------------------------

        async addSection() {
            await this.withSaving(async () => {
                const section = await api.sections.create(this.version.id, {
                    title: window.__i18n.untitled_section,
                });
                this.version.sections.push({ ...section, questions: [] });
            });
        },

        async updateSection(section, changes) {
            await this.withSaving(async () => {
                const updated = await api.sections.update(section.id, changes);
                Object.assign(section, updated);
            });
        },

        confirmDeleteSection(section) {
            this.askConfirm({
                title: window.__i18n.delete_section,
                body: window.__i18n.confirm_delete_section,
                onConfirm: () => this.deleteSection(section),
            });
        },

        async deleteSection(section) {
            await this.withSaving(async () => {
                await api.sections.destroy(section.id);
                this.version.sections = this.version.sections.filter((s) => s.id !== section.id);
            });
        },

        async reorderSections(orderedIds) {
            await this.withSaving(async () => {
                await api.sections.reorder(this.version.id, orderedIds);
                this.version.sections.sort((a, b) => orderedIds.indexOf(a.id) - orderedIds.indexOf(b.id));
            });
        },

        // --- Questions --------------------------------------------------

        async addQuestion(section, type) {
            await this.withSaving(async () => {
                const question = await api.questions.create(section.id, {
                    type,
                    label: window.__i18n.question_label_placeholder,
                    config: defaultConfigFor(type),
                });
                section.questions.push({ ...question, options: [] });
                this.activeQuestionId = question.id;
            });
        },

        async updateQuestion(question, changes) {
            await this.withSaving(async () => {
                const updated = await api.questions.update(question.id, changes);
                Object.assign(question, updated);
            });
        },

        confirmDeleteQuestion(section, question) {
            this.askConfirm({
                title: window.__i18n.delete_question,
                body: window.__i18n.confirm_delete_question,
                onConfirm: () => this.deleteQuestion(section, question),
            });
        },

        async deleteQuestion(section, question) {
            await this.withSaving(async () => {
                await api.questions.destroy(question.id);
                section.questions = section.questions.filter((q) => q.id !== question.id);
                if (this.activeQuestionId === question.id) this.activeQuestionId = null;
            });
        },

        async reorderQuestions(section, orderedIds) {
            await this.withSaving(async () => {
                await api.questions.reorder(section.id, orderedIds);
                section.questions.sort((a, b) => orderedIds.indexOf(a.id) - orderedIds.indexOf(b.id));
            });
        },

        // --- Options ------------------------------------------------------

        async addOption(question) {
            await this.withSaving(async () => {
                const option = await api.options.create(question.id, {
                    label: window.__i18n.option_label_placeholder,
                });
                question.options.push(option);
            });
        },

        async updateOption(option, changes) {
            await this.withSaving(async () => {
                const updated = await api.options.update(option.id, changes);
                Object.assign(option, updated);
            });
        },

        async deleteOption(question, option) {
            await this.withSaving(async () => {
                await api.options.destroy(option.id);
                question.options = question.options.filter((o) => o.id !== option.id);
            });
        },

        // --- Rules -----------------------------------------------------

        allQuestions() {
            return (this.version?.sections || []).flatMap((s) => s.questions.map((q) => ({ ...q, sectionTitle: s.title })));
        },

        async addRule() {
            const firstQuestion = this.allQuestions()[0];
            if (!firstQuestion) return;

            await this.withSaving(async () => {
                const rule = await api.rules.create(this.version.id, {
                    conditions: { question_id: firstQuestion.id, operator: 'is_answered' },
                    action: 'end_survey',
                });
                this.version.rules.push(rule);
            });
        },

        async updateRule(rule, changes) {
            await this.withSaving(async () => {
                const updated = await api.rules.update(rule.id, changes);
                Object.assign(rule, updated);
            });
        },

        async deleteRule(rule) {
            await this.withSaving(async () => {
                await api.rules.destroy(rule.id);
                this.version.rules = this.version.rules.filter((r) => r.id !== rule.id);
            });
        },

        // --- Lifecycle ---------------------------------------------------

        confirmPublish() {
            this.askConfirm({
                title: window.__i18n.publish,
                body: window.__i18n.publish_confirm,
                onConfirm: () => this.publish(),
                variant: 'primary',
            });
        },

        async publish() {
            await this.withSaving(async () => {
                this.survey = await api.surveys.publish(this.surveyId);
                this.viewVersion('published');
            });
        },

        async startNewDraft() {
            await this.withSaving(async () => {
                await api.surveys.createDraft(this.surveyId);
                await this.load();
            });
        },

        // --- Public link ---------------------------------------------------

        publicLinkCopied: false,

        async copyPublicLink() {
            const url = await api.surveys.publicLink(this.surveyId);
            await navigator.clipboard.writeText(url);
            this.publicLinkCopied = true;
            setTimeout(() => (this.publicLinkCopied = false), 2000);
        },

        async viewPublicLink() {
            const url = await api.surveys.publicLink(this.surveyId);
            window.open(url, '_blank', 'noopener');
        },

        // --- Field assignments (spec §7) -----------------------------------

        showAssignments: false,
        assignments: [],
        assignableUsers: [],
        assignmentsLoading: false,

        async openAssignments() {
            this.showAssignments = true;
            this.assignmentsLoading = true;
            try {
                [this.assignments, this.assignableUsers] = await Promise.all([
                    api.assignments.list(this.surveyId),
                    api.assignments.assignable(this.surveyId),
                ]);
            } finally {
                this.assignmentsLoading = false;
            }
        },

        async assignFieldUser(userId) {
            await this.withSaving(async () => {
                const assignment = await api.assignments.create(this.surveyId, userId);
                this.assignments.push(assignment);
                this.assignableUsers = this.assignableUsers.filter((u) => u.id !== userId);
            });
        },

        async unassignFieldUser(assignment) {
            await this.withSaving(async () => {
                await api.assignments.destroy(this.surveyId, assignment.id);
                this.assignments = this.assignments.filter((a) => a.id !== assignment.id);
                this.assignableUsers.push(assignment.user);
                this.assignableUsers.sort((a, b) => a.name.localeCompare(b.name));
            });
        },
    };
}
