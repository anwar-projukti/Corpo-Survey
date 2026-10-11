const BASE = '/api/v1/landing-content';

/**
 * Thin wrapper around axios for the public landing page content editor
 * (Super Admin-only). Unlike organizations/api.js, the controller returns
 * a plain attribute object rather than a JsonResource — no `.data.data`
 * unwrapping needed, just `.data`.
 */
export default {
    show: () => axios.get(BASE).then((r) => r.data),
    update: (payload) => axios.put(BASE, payload).then((r) => r.data),
    uploadLogo: (file) => {
        const formData = new FormData();
        formData.append('logo', file);

        return axios.post(`${BASE}/logo`, formData).then((r) => r.data);
    },
    removeLogo: () => axios.delete(`${BASE}/logo`),
};
