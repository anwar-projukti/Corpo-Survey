const BASE = '/api/v1/organizations';

/**
 * Thin wrapper around axios for organization management (spec §13
 * "System Admin").
 */
export default {
    list: () => axios.get(BASE).then((r) => r.data.data),
    listTrashed: () => axios.get(BASE, { params: { trashed: 1 } }).then((r) => r.data.data),
    create: (payload) => axios.post(BASE, payload).then((r) => r.data.data),
    update: (id, payload) => axios.patch(`${BASE}/${id}`, payload).then((r) => r.data.data),
    delete: (id) => axios.delete(`${BASE}/${id}`),
    restore: (id) => axios.post(`${BASE}/${id}/restore`).then((r) => r.data.data),
    forceDelete: (id) => axios.delete(`${BASE}/${id}/force`),
};
