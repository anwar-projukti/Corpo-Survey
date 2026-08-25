const BASE = '/api/v1';

/**
 * Thin wrapper around axios for the survey builder's calls.
 *
 * Every method except `surveys.list` returns the resource itself (already
 * unwrapped from Laravel's JsonResource {data: ...} envelope) — callers
 * never need to reach into `.data` themselves. `surveys.list` is paginated
 * and returns the full { data: [...], links, meta } page object, since
 * callers need the pagination metadata too.
 *
 * Throws axios's own error (with .response.data.errors for a 422) on
 * failure — callers are expected to catch and surface that, not this module.
 */
export default {
    surveys: {
        list: (params = {}) => axios.get(`${BASE}/surveys`, { params }).then((r) => r.data),
        create: (data) => axios.post(`${BASE}/surveys`, data).then((r) => r.data.data),
        show: (id) => axios.get(`${BASE}/surveys/${id}`).then((r) => r.data.data),
        update: (id, data) => axios.patch(`${BASE}/surveys/${id}`, data).then((r) => r.data.data),
        destroy: (id) => axios.delete(`${BASE}/surveys/${id}`).then((r) => r.data),
        publish: (id) => axios.post(`${BASE}/surveys/${id}/publish`).then((r) => r.data.data),
        createDraft: (id) => axios.post(`${BASE}/surveys/${id}/draft`).then((r) => r.data.data),
    },

    versions: {
        show: (id) => axios.get(`${BASE}/survey-versions/${id}`).then((r) => r.data.data),
    },

    sections: {
        create: (versionId, data) =>
            axios.post(`${BASE}/survey-versions/${versionId}/sections`, data).then((r) => r.data.data),
        update: (id, data) => axios.patch(`${BASE}/survey-sections/${id}`, data).then((r) => r.data.data),
        destroy: (id) => axios.delete(`${BASE}/survey-sections/${id}`).then((r) => r.data),
        reorder: (versionId, order) =>
            axios.post(`${BASE}/survey-versions/${versionId}/sections/reorder`, { order }).then((r) => r.data),
    },

    questions: {
        create: (sectionId, data) =>
            axios.post(`${BASE}/survey-sections/${sectionId}/questions`, data).then((r) => r.data.data),
        update: (id, data) => axios.patch(`${BASE}/survey-questions/${id}`, data).then((r) => r.data.data),
        destroy: (id) => axios.delete(`${BASE}/survey-questions/${id}`).then((r) => r.data),
        reorder: (sectionId, order) =>
            axios.post(`${BASE}/survey-sections/${sectionId}/questions/reorder`, { order }).then((r) => r.data),
    },

    options: {
        create: (questionId, data) =>
            axios.post(`${BASE}/survey-questions/${questionId}/options`, data).then((r) => r.data.data),
        update: (id, data) => axios.patch(`${BASE}/question-options/${id}`, data).then((r) => r.data.data),
        destroy: (id) => axios.delete(`${BASE}/question-options/${id}`).then((r) => r.data),
        reorder: (questionId, order) =>
            axios.post(`${BASE}/survey-questions/${questionId}/options/reorder`, { order }).then((r) => r.data),
    },

    rules: {
        create: (versionId, data) =>
            axios.post(`${BASE}/survey-versions/${versionId}/rules`, data).then((r) => r.data.data),
        update: (id, data) => axios.patch(`${BASE}/survey-rules/${id}`, data).then((r) => r.data.data),
        destroy: (id) => axios.delete(`${BASE}/survey-rules/${id}`).then((r) => r.data),
    },
};
