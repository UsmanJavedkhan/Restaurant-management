import axios from 'axios';

export const api = axios.create({ baseURL: '/api/v1', timeout: 20000, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, withCredentials: true });
export function updateCsrf(data) {
    if (data.csrf_token) api.defaults.headers.common['X-CSRF-TOKEN'] = data.csrf_token;
}
export function errorMessage(error) {
    if (error.response?.status === 419) return 'Your session expired. Refresh the page and try again.';
    if (error.response?.status === 401) return 'Please sign in to continue.';
    if (error.response?.status >= 500) return 'Something went wrong. Please try again.';
    const errors = error.response?.data?.errors;
    if (errors) return Object.values(errors).flat().join(' ');
    return error.response?.data?.message || 'Could not connect to the restaurant. Please try again.';
}
