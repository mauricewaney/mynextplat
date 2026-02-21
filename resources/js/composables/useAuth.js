import { ref, computed } from 'vue'
import { apiGet, apiPost, apiPut } from '../utils/api'
import { resetUserGameMap } from './useUserGames'

// Shared auth state (singleton)
const user = ref(null)
const loading = ref(true)
const initialized = ref(false)

export function useAuth() {
    const isAuthenticated = computed(() => !!user.value)
    const isAdmin = computed(() => user.value?.is_admin === true)
    const notifyNewGuides = computed(() => user.value?.notify_new_guides ?? true)
    const profilePublic = computed(() => user.value?.profile_public ?? false)
    const profileSlug = computed(() => user.value?.profile_slug || user.value?.id)
    const profileUrl = computed(() => profileSlug.value ? `${window.location.origin}/u/${profileSlug.value}` : null)

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
        } catch (error) {
            console.error('Failed to logout:', error)
        }
        user.value = null
        resetUserGameMap()
    }

    /**
     * Update user preferences
     */
    async function updatePreferences(prefs) {
        try {
            const data = await apiPut('/user/preferences', prefs)
            // Update local state
            if (user.value) {
                if (data.notify_new_guides !== undefined) {
                    user.value.notify_new_guides = data.notify_new_guides
                }
            }
            return data
        } catch (error) {
            console.error('Failed to update preferences:', error)
            throw error
        }
    }

    return {
        user,
        loading,
        initialized,
        isAuthenticated,
        isAdmin,
        notifyNewGuides,
        profilePublic,
        profileSlug,
        profileUrl,
        fetchUser,
        initAuth,
        loginWithGoogle,
        logout,
        updatePreferences,
    }
}
