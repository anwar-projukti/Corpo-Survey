import pricingPlansApi from './api';

const emptyForm = () => ({
    name_en: '',
    name_bn: '',
    price_en: '',
    price_bn: '',
    description_en: '',
    description_bn: '',
    features_en_text: '',
    features_bn_text: '',
    button_text_en: '',
    button_text_bn: '',
    button_target: 'register',
    highlighted: false,
    trial_days: '',
});

/**
 * Pricing plan management (public landing page tiers) — a genuine
 * add/edit/delete/reorder list, unlike the single-row landing content
 * editor. Each plan's feature list is edited as one newline-separated
 * textarea per locale rather than repeatable row inputs — simpler to
 * build and use for a short bullet list, converted to/from the array the
 * API stores on load/save.
 */
export default function pricingPlanManagement() {
    return {
        plans: [],
        loading: true,
        loadError: null,

        showForm: false,
        editingId: null,
        form: emptyForm(),
        submitting: false,
        submitError: null,

        reordering: false,

        confirmOpen: false,
        confirmTitle: '',
        confirmBody: '',
        confirmBusy: false,
        confirmAction: null,

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            this.loadError = null;

            try {
                this.plans = await pricingPlansApi.list();
            } catch {
                this.loadError = window.__i18n.load_failed;
            } finally {
                this.loading = false;
            }
        },

        openCreateForm() {
            this.editingId = null;
            this.form = emptyForm();
            this.submitError = null;
            this.showForm = true;
        },

        openEditForm(plan) {
            this.editingId = plan.id;
            this.form = {
                name_en: plan.name_en,
                name_bn: plan.name_bn,
                price_en: plan.price_en,
                price_bn: plan.price_bn,
                description_en: plan.description_en || '',
                description_bn: plan.description_bn || '',
                features_en_text: (plan.features_en || []).join('\n'),
                features_bn_text: (plan.features_bn || []).join('\n'),
                button_text_en: plan.button_text_en,
                button_text_bn: plan.button_text_bn,
                button_target: plan.button_target,
                highlighted: plan.highlighted,
                trial_days: plan.trial_days ?? '',
            };
            this.submitError = null;
            this.showForm = true;
        },

        closeForm() {
            this.showForm = false;
        },

        toLines(text) {
            return text
                .split('\n')
                .map((line) => line.trim())
                .filter((line) => line.length > 0);
        },

        async submit() {
            this.submitting = true;
            this.submitError = null;

            const payload = {
                name_en: this.form.name_en,
                name_bn: this.form.name_bn,
                price_en: this.form.price_en,
                price_bn: this.form.price_bn,
                description_en: this.form.description_en || null,
                description_bn: this.form.description_bn || null,
                features_en: this.toLines(this.form.features_en_text),
                features_bn: this.toLines(this.form.features_bn_text),
                button_text_en: this.form.button_text_en,
                button_text_bn: this.form.button_text_bn,
                button_target: this.form.button_target,
                highlighted: this.form.highlighted,
                trial_days: this.form.trial_days === '' ? null : Number(this.form.trial_days),
            };

            try {
                if (this.editingId) {
                    const updated = await pricingPlansApi.update(this.editingId, payload);
                    this.plans = this.plans.map((p) => (p.id === updated.id ? updated : p));
                } else {
                    const created = await pricingPlansApi.create(payload);
                    this.plans = [...this.plans, created];
                }
                this.showForm = false;
            } catch (error) {
                this.submitError = error?.response?.data?.message || window.__i18n.save_failed;
            } finally {
                this.submitting = false;
            }
        },

        askConfirm({ title, body, onConfirm }) {
            this.confirmTitle = title;
            this.confirmBody = body;
            this.confirmAction = onConfirm;
            this.confirmOpen = true;
        },

        async runConfirm() {
            if (! this.confirmAction) {
                return;
            }

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

        confirmDelete(plan) {
            this.askConfirm({
                title: window.__i18n.delete_title,
                body: window.__i18n.delete_confirm.replace(':name', plan.name_en),
                onConfirm: () => this.deletePlan(plan),
            });
        },

        async deletePlan(plan) {
            try {
                await pricingPlansApi.delete(plan.id);
                this.plans = this.plans.filter((p) => p.id !== plan.id);
            } catch {
                this.loadError = window.__i18n.save_failed;
            }
        },

        async reorder(orderedIds) {
            this.reordering = true;

            const previous = this.plans;
            this.plans = orderedIds.map((id) => this.plans.find((p) => p.id === id));

            try {
                await pricingPlansApi.reorder(orderedIds);
            } catch {
                this.plans = previous;
                this.loadError = window.__i18n.save_failed;
            } finally {
                this.reordering = false;
            }
        },
    };
}
