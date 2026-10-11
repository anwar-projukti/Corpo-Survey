const BASE = '/api/v1';

/**
 * Thin wrapper around axios for exports (spec §10.1/§10.2) — starting a
 * survey or organization-wide export and reading export history. Starting
 * an export only ever returns the created (still-pending) job; the admin
 * UI polls exportsApi.history() to see it flip to completed/failed.
 */
export default {
    history: (params = {}) => axios.get(`${BASE}/exports`, { params }).then((r) => r.data),

    exportSurvey: (surveyId, payload) =>
        axios.post(`${BASE}/surveys/${surveyId}/export`, payload).then((r) => r.data.data),

    exportOrganization: (organizationId, payload) =>
        axios.post(`${BASE}/organizations/${organizationId}/export`, payload).then((r) => r.data.data),
};
