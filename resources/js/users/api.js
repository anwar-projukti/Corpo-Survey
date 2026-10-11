const BASE = '/api/v1/teammates';

/**
 * Thin wrapper around axios for teammate management (spec §13 "Users &
 * Permissions").
 */
export default {
    list: () => axios.get(BASE).then((r) => r.data.data),
    create: (payload) => axios.post(BASE, payload).then((r) => r.data.data),
    update: (id, payload) => axios.patch(`${BASE}/${id}`, payload).then((r) => r.data.data),
    delete: (id) => axios.delete(`${BASE}/${id}`),
};
