import { ref, computed } from 'vue'
import { apiGet, apiPost } from '../utils/api'

// Shared auth state (singleton)
const user = ref(null)
const loading = ref(true)
const initialized = ref(false)

export function useAuth() {
    const isAuthenticated = computed(() => !!user.value)
    const isAdmin = computed(() => user.value?.is_admin === true)

    /**
     * Fetch the current user from the API
     */
    async function fetchUser() {
        loading.value = true
        try {
            const data = await apiGet('/user')
            user.value = data
        } catch (error) {
            console.error('Failed to fetch user:', error)
            user.value = null
        } finally {
            loading.value = false
            initialized.value = true
        }
    }

    /**
     * Initialize auth state (call once on app mount)
     */
    async function initAuth() {
        if (!initialized.value) {
            await fetchUser()
        }
    }

    /**
     * Redirect to Google OAuth login
     */
    function loginWithGoogle() {
        window.location.href = '/auth/google'
    }

    /**
     * Log out the current user
     */
    async function logout() {
        try {
            await apiPost('/logout')
            user.value = null
        } catch (error) {
            console.error('Failed to logout:', error)
            // Still clear local state even if request fails
            user.value = null
        }
    }

    return {
        user,
        loading,
        initialized,
        isAuthenticated,
        isAdmin,
        fetchUser,
        initAuth,
        loginWithGoogle,
        logout,
    }
}
