import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');

// Sanctum SPA auth: same-origin requests to /api/v1/... authenticate via
// the ordinary session cookie rather than a bearer token.
window.axios.defaults.withCredentials = true;
