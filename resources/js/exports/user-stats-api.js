const BASE = '/api/v1';

/**
 * Thin wrapper around axios for user productivity reporting (spec §10.3).
 */
export default {
    organizationStats: (organizationId, params = {}) =>
        axios.get(`${BASE}/organizations/${organizationId}/user-stats`, { params }).then((r) => r.data),

    userStats: (userId) => axios.get(`${BASE}/users/${userId}/stats`).then((r) => r.data),
};
