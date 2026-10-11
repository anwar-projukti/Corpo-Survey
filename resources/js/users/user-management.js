import teammatesApi from './api';

const ASSIGNABLE_ROLES = ['Editor', 'Field User', 'Viewer'];

/**
 * Teammate management (spec §13 "Users & Permissions") — list an
 * organization's users, add a new one with a temporary password the Org
 * Admin hands off out of band (no invite-email flow exists yet), edit an
 * existing one's name/role/password, or deactivate one. An Org Admin
 * teammate row never appears here (UserPolicy already refuses update/
 * delete on one), so every row this screen shows is always editable.
 */
export default function userManagement() {
    return {
        teammates: [],
        loading: true,
        loadError: null,

        showForm: false,
        editingId: null,
        formName: '',
        formEmail: '',
        formPassword: '',
        formPasswordConfirmation: '',
        formRole: 'Field User',
        submitting: false,
        submitError: null,

        deletingId: null,
        confirmOpen: false,
        confirmTitle: '',
        confirmBody: '',
        confirmBusy: false,
        confirmAction: null,

        async init() {
            await this.load();
        },

        get assignableRoles() {
            return ASSIGNABLE_ROLES;
        },

        get isEditing() {
            return this.editingId !== null;
        },

        async load() {
            this.loading = true;
            this.loadError = null;

            try {
                this.teammates = await teammatesApi.list();
            } catch {
                this.loadError = window.__i18n.load_failed;
            } finally {
                this.loading = false;
            }
        },

        openForm() {
            this.editingId = null;
            this.showForm = true;
            this.formName = '';
            this.formEmail = '';
            this.formPassword = '';
            this.formPasswordConfirmation = '';
            this.formRole = 'Field User';
            this.submitError = null;
        },

        openEditForm(teammate) {
            this.editingId = teammate.id;
            this.showForm = true;
            this.formName = teammate.name;
            this.formEmail = teammate.email;
            this.formPassword = '';
            this.formPasswordConfirmation = '';
            this.formRole = teammate.role;
            this.submitError = null;
        },

        async submit() {
            this.submitting = true;
            this.submitError = null;

            try {
                if (this.isEditing) {
                    const payload = { name: this.formName, role: this.formRole };
                    if (this.formPassword) {
                        payload.password = this.formPassword;
                        payload.password_confirmation = this.formPasswordConfirmation;
                    }
                    const updated = await teammatesApi.update(this.editingId, payload);
                    this.teammates = this.teammates.map((t) => (t.id === updated.id ? updated : t));
                } else {
                    await teammatesApi.create({
                        name: this.formName,
                        email: this.formEmail,
                        password: this.formPassword,
                        password_confirmation: this.formPasswordConfirmation,
                        role: this.formRole,
                    });
                    await this.load();
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

        confirmDeleteTeammate(teammate) {
            this.askConfirm({
                title: window.__i18n.delete_title,
                body: window.__i18n.delete_confirm.replace(':name', teammate.name),
                onConfirm: () => this.deleteTeammate(teammate),
            });
        },

        async deleteTeammate(teammate) {
            this.deletingId = teammate.id;

            try {
                await teammatesApi.delete(teammate.id);
                this.teammates = this.teammates.filter((t) => t.id !== teammate.id);
            } catch {
                this.loadError = window.__i18n.delete_failed;
            } finally {
                this.deletingId = null;
            }
        },
    };
}
