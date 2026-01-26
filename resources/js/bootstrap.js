import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

/**
 * CSRF handling for Laravel Sanctum SPA authentication
 * Patches global fetch to automatically handle CSRF tokens
 */

let csrfInitialized = false;
const originalFetch = window.fetch;

// Get XSRF token from cookies
function getXsrfToken() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (match) {
        return decodeURIComponent(match[1]);
    }
    return null;
}

// Initialize CSRF cookie
async function ensureCsrf() {
    if (csrfInitialized) return;

    try {
        await originalFetch('/sanctum/csrf-cookie', {
            credentials: 'include',
        });
        csrfInitialized = true;
    } catch (e) {
        console.error('Failed to get CSRF cookie:', e);
    }
}

// Patch global fetch to handle CSRF
window.fetch = async function(url, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const isApiRequest = typeof url === 'string' && url.startsWith('/api');
    const isMutatingRequest = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

    // For mutating API requests, ensure CSRF is initialized
    if (isApiRequest && isMutatingRequest) {
        await ensureCsrf();

        // Add CSRF token header
        const xsrfToken = getXsrfToken();
        if (xsrfToken) {
            options.headers = {
                ...options.headers,
                'X-XSRF-TOKEN': xsrfToken,
            };
        }

        // Ensure credentials are included
        options.credentials = 'include';
    }

    // Make the request
    let response = await originalFetch(url, options);

    // Handle 419 CSRF mismatch - retry once
    if (response.status === 419 && isApiRequest && isMutatingRequest) {
        csrfInitialized = false;
        await ensureCsrf();

        const xsrfToken = getXsrfToken();
        if (xsrfToken) {
            options.headers = {
                ...options.headers,
                'X-XSRF-TOKEN': xsrfToken,
            };
        }

        response = await originalFetch(url, options);
    }

    return response;
};

// Initialize CSRF on page load for authenticated users
ensureCsrf();
