const BASE = '/api/v1/pricing-plans';

/**
 * Thin wrapper around axios for pricing plan management (Super Admin-only).
 * Controllers return plain model attribute objects, not JsonResources —
 * no `.data.data` unwrapping, just `.data`.
 */
export default {
    list: () => axios.get(BASE).then((r) => r.data),
    create: (payload) => axios.post(BASE, payload).then((r) => r.data),
    update: (id, payload) => axios.patch(`${BASE}/${id}`, payload).then((r) => r.data),
    delete: (id) => axios.delete(`${BASE}/${id}`),
    reorder: (order) => axios.post(`${BASE}/reorder`, { order }),
};
