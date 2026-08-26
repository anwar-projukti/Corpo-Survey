const BASE = '/api/v1';

/**
 * Thin wrapper around axios for the analytics dashboard (spec §9).
 */
export default {
    show: (surveyId, params = {}) =>
        axios.get(`${BASE}/surveys/${surveyId}/analytics`, { params }).then((r) => r.data.data),
};
