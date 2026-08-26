const BASE = '/api/v1';

/**
 * Thin wrapper around axios for the analytics dashboard and custom report
 * builder (spec §9).
 */
export default {
    show: (surveyId, params = {}) =>
        axios.get(`${BASE}/surveys/${surveyId}/analytics`, { params }).then((r) => r.data.data),

    listReports: (surveyId) => axios.get(`${BASE}/surveys/${surveyId}/saved-reports`).then((r) => r.data.data),
    createReport: (surveyId, payload) =>
        axios.post(`${BASE}/surveys/${surveyId}/saved-reports`, payload).then((r) => r.data.data),
    updateReport: (reportId, payload) =>
        axios.patch(`${BASE}/saved-reports/${reportId}`, payload).then((r) => r.data.data),
    deleteReport: (reportId) => axios.delete(`${BASE}/saved-reports/${reportId}`).then((r) => r.data),
    runReport: (reportId) => axios.get(`${BASE}/saved-reports/${reportId}/run`).then((r) => r.data.data),
};
