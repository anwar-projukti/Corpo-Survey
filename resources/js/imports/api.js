const BASE = '/api/v1';

/**
 * Thin wrapper around axios for respondent import (spec §10.4) — upload,
 * confirm the column mapping, poll a single import's status, and list
 * import history.
 */
export default {
    history: (params = {}) => axios.get(`${BASE}/imports`, { params }).then((r) => r.data),

    upload: (file) => {
        const form = new FormData();
        form.append('file', file);

        return axios.post(`${BASE}/imports`, form, { headers: { 'Content-Type': 'multipart/form-data' } }).then((r) => r.data.data);
    },

    show: (importJobId) => axios.get(`${BASE}/imports/${importJobId}`).then((r) => r.data.data),

    confirm: (importJobId, columnMapping) =>
        axios.post(`${BASE}/imports/${importJobId}/confirm`, { column_mapping: columnMapping }).then((r) => r.data.data),
};
