const BASE = '/api/v1';

/**
 * Thin wrapper around axios for response management (spec §8) — the
 * advanced response table, detail view, review workflow, notes, and
 * audited answer edits.
 */
export default {
    list: (surveyId, params = {}) => axios.get(`${BASE}/surveys/${surveyId}/responses`, { params }).then((r) => r.data),
    show: (surveyId, responseId) =>
        axios.get(`${BASE}/surveys/${surveyId}/responses/${responseId}`).then((r) => r.data.data),
    destroy: (surveyId, responseId) =>
        axios.delete(`${BASE}/surveys/${surveyId}/responses/${responseId}`).then((r) => r.data),

    review: (responseId, reviewStatus) =>
        axios.post(`${BASE}/responses/${responseId}/review`, { review_status: reviewStatus }).then((r) => r.data.data),

    addNote: (responseId, body) =>
        axios.post(`${BASE}/responses/${responseId}/notes`, { body }).then((r) => r.data.data),

    updateAnswer: (answerId, value) =>
        axios.patch(`${BASE}/response-answers/${answerId}`, { value }).then((r) => r.data.data),
};
