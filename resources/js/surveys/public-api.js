const BASE = '/api/v1/public/surveys';

/**
 * Thin wrapper around axios for the respondent-facing public survey form.
 * Unlike surveys/api.js this never assumes a stable session identity
 * beyond the respondent cookie PublicSurveyAccess sets — every call is
 * scoped to one surveyId, since a respondent only ever interacts with one
 * survey per page load.
 */
export default {
    show: (surveyId) => axios.get(`${BASE}/${surveyId}`).then((r) => r.data.data),

    requestOtp: (surveyId, destination, captchaToken) =>
        axios.post(`${BASE}/${surveyId}/otp`, { destination, captcha_token: captchaToken }).then((r) => r.data),

    verifyOtp: (surveyId, destination, code) =>
        axios.post(`${BASE}/${surveyId}/otp/verify`, { destination, code }).then((r) => r.data),

    submit: (surveyId, payload) => axios.post(`${BASE}/${surveyId}/responses`, payload).then((r) => r.data),
};
