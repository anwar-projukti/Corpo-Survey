import organizationsApi from './api';

/**
 * Organization management (spec §13 "System Admin") — list every
 * organization, create a new one, and toggle an existing one between
 * active/suspended.
 */
export default function organizationManagement() {
    return {
        organizations: [],
        loading: true,
        loadError: null,

        showTrashed: false,
        trashedOrganizations: [],
        trashedLoaded: false,
        trashedLoading: false,
        trashedLoadError: null,

        showForm: false,
        formName: '',
        formSlug: '',
        submitting: false,
        submitError: null,

        togglingId: null,
        deletingId: null,
        restoringId: null,
        forceDeletingId: null,

        // A single confirm modal shared by delete and permanently-delete —
        // `confirmAction` holds the callback to run if the user confirms,
        // so the modal itself stays generic instead of one copy per action.
        confirmOpen: false,
        confirmTitle: '',
        confirmBody: '',
        confirmDanger: false,
        confirmBusy: false,
        confirmAction: null,

        askConfirm({ title, body, danger = false, onConfirm }) {
            this.confirmTitle = title;
            this.confirmBody = body;
            this.confirmDanger = danger;
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

        get activeCount() {
            return this.organizations.filter((o) => o.status === 'active').length;
        },

        get suspendedCount() {
            return this.organizations.filter((o) => o.status === 'suspended').length;
        },

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            this.loadError = null;

            try {
                this.organizations = await organizationsApi.list();
            } catch {
                this.loadError = window.__i18n.load_failed;
            } finally {
                this.loading = false;
            }
        },

        async toggleTrashedView() {
            this.showTrashed = ! this.showTrashed;

            if (this.showTrashed && ! this.trashedLoaded) {
                await this.loadTrashed();
            }
        },

        async loadTrashed() {
            this.trashedLoading = true;
            this.trashedLoadError = null;

            try {
                this.trashedOrganizations = await organizationsApi.listTrashed();
                this.trashedLoaded = true;
            } catch {
                this.trashedLoadError = window.__i18n.load_failed;
            } finally {
                this.trashedLoading = false;
            }
        },

        async restoreOrganization(organization) {
            this.restoringId = organization.id;

            try {
                await organizationsApi.restore(organization.id);
                this.trashedOrganizations = this.trashedOrganizations.filter((o) => o.id !== organization.id);
                await this.load();
            } catch {
                this.trashedLoadError = window.__i18n.restore_failed;
            } finally {
                this.restoringId = null;
            }
        },

        confirmForceDeleteOrganization(organization) {
            this.askConfirm({
                title: window.__i18n.force_delete_title,
                body: window.__i18n.force_delete_confirm.replace(':name', organization.name),
                danger: true,
                onConfirm: () => this.forceDeleteOrganization(organization),
            });
        },

        async forceDeleteOrganization(organization) {
            this.forceDeletingId = organization.id;

            try {
                await organizationsApi.forceDelete(organization.id);
                this.trashedOrganizations = this.trashedOrganizations.filter((o) => o.id !== organization.id);
            } catch {
                this.trashedLoadError = window.__i18n.force_delete_failed;
            } finally {
                this.forceDeletingId = null;
            }
        },

        openForm() {
            this.showForm = true;
            this.formName = '';
            this.formSlug = '';
            this.submitError = null;
        },

        async submit() {
            this.submitting = true;
            this.submitError = null;

            try {
                await organizationsApi.create({
                    name: this.formName,
                    slug: this.formSlug,
                });
                this.showForm = false;
                await this.load();
            } catch (error) {
                this.submitError = error?.response?.data?.message || window.__i18n.save_failed;
            } finally {
                this.submitting = false;
            }
        },

        async toggleStatus(organization) {
            this.togglingId = organization.id;

            try {
                const status = organization.status === 'active' ? 'suspended' : 'active';
                const updated = await organizationsApi.update(organization.id, { status });
                organization.status = updated.status;
            } catch {
                this.loadError = window.__i18n.save_failed;
            } finally {
                this.togglingId = null;
            }
        },

        confirmDeleteOrganization(organization) {
            this.askConfirm({
                title: window.__i18n.delete_title,
                body: window.__i18n.delete_confirm.replace(':name', organization.name),
                danger: true,
                onConfirm: () => this.deleteOrganization(organization),
            });
        },

        async deleteOrganization(organization) {
            this.deletingId = organization.id;

            try {
                await organizationsApi.delete(organization.id);
                this.organizations = this.organizations.filter((o) => o.id !== organization.id);
                // Invalidate the cached trashed list rather than patching
                // it in place — this organization's counts/admin_emails as
                // of a moment ago may already be stale by the time the
                // trashed view is opened, so a full re-fetch is simpler
                // and safer than trying to keep both lists in sync by hand.
                this.trashedLoaded = false;
            } catch {
                this.loadError = window.__i18n.delete_failed;
            } finally {
                this.deletingId = null;
            }
        },
    };
}
