const BASE = '/api/v1/my';

/**
 * Thin wrapper around axios for the door-to-door field collection portal
 * (spec §7). Every call here is authenticated (the ordinary session
 * cookie, same as the admin builder) — there is no CAPTCHA/OTP/respondent-
 * cookie layer, unlike resources/js/surveys/public-api.js, since the
 * submitter's identity is already the logged-in field user.
 */
export default {
    listSurveys: () => axios.get(`${BASE}/surveys`).then((r) => r.data.data),
    showSurvey: (surveyId) => axios.get(`${BASE}/surveys/${surveyId}`).then((r) => r.data.data),
    stats: () => axios.get(`${BASE}/stats`).then((r) => r.data.data),
    listResponses: (page = 1) => axios.get(`${BASE}/responses`, { params: { page } }).then((r) => r.data),
    showResponse: (responseId) => axios.get(`${BASE}/responses/${responseId}`).then((r) => r.data.data),
};
