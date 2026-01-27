/**
 * API utility for making authenticated requests with Laravel Sanctum
 */

const API_BASE = '/api'
let csrfInitialized = false

/**
 * Get CSRF cookie from Sanctum (required before POST/PUT/DELETE)
 */
async function ensureCsrf() {
    if (csrfInitialized) return

    await fetch('/sanctum/csrf-cookie', {
        credentials: 'include',
    })
    csrfInitialized = true
}

/**
 * Get XSRF token from cookies
 */
function getXsrfToken() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
    if (match) {
        return decodeURIComponent(match[1])
    }
    return null
}

/**
 * Make an API request with credentials included
 */
export async function api(endpoint, options = {}) {
    const url = endpoint.startsWith('http') ? endpoint : `${API_BASE}${endpoint}`

    // For non-GET requests, ensure we have CSRF token
    if (options.method && options.method !== 'GET') {
        await ensureCsrf()
    }

    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
    }

    // Add XSRF token header
    const xsrfToken = getXsrfToken()
    if (xsrfToken) {
        headers['X-XSRF-TOKEN'] = xsrfToken
    }

    const response = await fetch(url, {
        ...options,
        credentials: 'include',
        headers,
    })

    // Handle 401 unauthorized
    if (response.status === 401) {
        console.warn('Unauthorized request')
        csrfInitialized = false // Reset CSRF on auth failure
    }

    // Handle 419 CSRF mismatch - retry once
    if (response.status === 419) {
        csrfInitialized = false
        await ensureCsrf()

        const retryHeaders = { ...headers }
        const retryToken = getXsrfToken()
        if (retryToken) {
            retryHeaders['X-XSRF-TOKEN'] = retryToken
        }

        return fetch(url, {
            ...options,
            credentials: 'include',
            headers: retryHeaders,
        })
    }

    return response
}

/**
 * GET request
 */
export async function apiGet(endpoint) {
    const response = await api(endpoint)
    if (!response.ok) {
        throw new Error(`API error: ${response.status}`)
    }
    return response.json()
}

/**
 * POST request
 */
export async function apiPost(endpoint, data = {}) {
    const response = await api(endpoint, {
        method: 'POST',
        body: JSON.stringify(data),
    })
    return response
}

/**
 * PUT request
 */
export async function apiPut(endpoint, data = {}) {
    const response = await api(endpoint, {
        method: 'PUT',
        body: JSON.stringify(data),
    })
    if (!response.ok) {
        throw new Error(`API error: ${response.status}`)
    }
    return response.json()
}

/**
 * DELETE request
 */
export async function apiDelete(endpoint) {
    const response = await api(endpoint, {
        method: 'DELETE',
    })
    return response
}

/**
 * Initialize CSRF token (call on app mount)
 */
export async function initCsrf() {
    await ensureCsrf()
}
