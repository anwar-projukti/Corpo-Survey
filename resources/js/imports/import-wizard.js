import importsApi from './api';

const POLL_INTERVAL_MS = 2000;
const TERMINAL_STATUSES = ['completed', 'failed'];

/**
 * The respondent import wizard (spec §10.4): upload -> preview detected
 * columns -> map columns -> confirm (queued) -> poll until the queued job
 * finishes -> summary. One `step` at a time rather than a single giant
 * form, since each step depends on the previous step's server response
 * (detected columns aren't known until after upload).
 */
export default function importWizard() {
    return {
        step: 'upload', // upload -> map -> processing -> summary
        importJob: null,

        uploading: false,
        uploadError: null,
        dragOver: false,

        mapping: { name: '', email: '', phone: '' },
        confirming: false,
        confirmError: null,

        pollTimer: null,

        init() {
            this.$watch('step', () => {});
        },

        destroy() {
            this.stopPolling();
        },

        onFileChange(event) {
            const file = event.target.files?.[0];
            if (file) this.upload(file);
        },

        onDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer?.files?.[0];
            if (file) this.upload(file);
        },

        async upload(file) {
            this.uploading = true;
            this.uploadError = null;

            try {
                this.importJob = await importsApi.upload(file);
                this.mapping = { name: '', email: '', phone: '' };
                this.step = 'map';
            } catch (error) {
                this.uploadError = error?.response?.data?.errors?.file?.[0] || window.__i18n.upload_failed;
            } finally {
                this.uploading = false;
            }
        },

        get detectedColumns() {
            return this.importJob?.detected_columns || [];
        },

        async confirmMapping() {
            if (!this.mapping.name) {
                this.confirmError = window.__i18n.map_name_required;

                return;
            }

            this.confirming = true;
            this.confirmError = null;

            const columnMapping = Object.fromEntries(
                Object.entries(this.mapping).filter(([, column]) => column !== ''),
            );

            try {
                this.importJob = await importsApi.confirm(this.importJob.id, columnMapping);
                this.step = 'processing';
                this.startPolling();
            } catch (error) {
                this.confirmError = error?.response?.data?.message || window.__i18n.confirm_failed;
            } finally {
                this.confirming = false;
            }
        },

        startPolling() {
            this.stopPolling();

            this.pollTimer = setInterval(async () => {
                this.importJob = await importsApi.show(this.importJob.id);

                if (TERMINAL_STATUSES.includes(this.importJob.status)) {
                    this.stopPolling();
                    this.step = 'summary';
                    this.$dispatch('import-completed');
                }
            }, POLL_INTERVAL_MS);
        },

        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        startNew() {
            this.step = 'upload';
            this.importJob = null;
            this.uploadError = null;
            this.confirmError = null;
        },
    };
}
