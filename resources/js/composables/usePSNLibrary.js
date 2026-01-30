import { ref, computed, readonly } from 'vue'
import { useAuth } from './useAuth'

// Shared state (singleton pattern)
const psnUsername = ref('')
const psnLoading = ref(false)
const psnError = ref('')
const psnUser = ref(null)
const psnStats = ref({ total_psn_games: 0, matched_games: 0, unmatched_games: 0, has_guide: 0 })
const psnAllGameIds = ref([])
const psnHasGuideIds = ref([])
const psnHasGuideOnly = ref(true)
const psnUnmatchedTitles = ref([])
const psnGameIds = ref(null) // The filtered game IDs to pass to the game list

export function usePSNLibrary() {
    const { isAdmin, isAuthenticated } = useAuth()

    const psnFilteredCount = computed(() => {
        if (psnHasGuideOnly.value) {
            return psnStats.value.has_guide
        }
        return psnStats.value.matched_games
    })

    const isPsnLoaded = computed(() => !!psnUser.value)

    async function loadMyLibrary() {
        psnLoading.value = true
        psnError.value = ''

        try {
            const response = await fetch('/api/psn/my-owned-games', {
                headers: {
                    'Accept': 'application/json',
                },
            })
            const data = await response.json()

            if (!response.ok) {
                throw new Error(data.message || 'Failed to load library')
            }

            psnUser.value = data.user
            psnAllGameIds.value = data.game_ids
            psnHasGuideIds.value = data.has_guide_ids || []
            psnUnmatchedTitles.value = data.unmatched_titles || []
            psnStats.value = {
                total_psn_games: data.stats.total_owned_games,
                matched_games: data.stats.matched_games,
                unmatched_games: data.stats.unmatched_games,
                has_guide: data.stats.has_guide,
            }
            psnHasGuideOnly.value = true
            applyPsnFilters()
        } catch (e) {
            psnError.value = e.message
        } finally {
            psnLoading.value = false
        }
    }

    async function lookupPSN(username) {
        const name = username || psnUsername.value
        if (!name.trim()) return

        psnLoading.value = true
        psnError.value = ''

        try {
            const response = await fetch(`/api/psn/lookup/${encodeURIComponent(name.trim())}`, {
                headers: {
                    'Accept': 'application/json',
                },
            })
            const data = await response.json()

            if (!response.ok) {
                throw new Error(data.message || 'Failed to lookup user')
            }

            psnUser.value = data.user
            psnAllGameIds.value = data.game_ids
            psnHasGuideIds.value = data.has_guide_ids || []
            psnUnmatchedTitles.value = data.unmatched_titles || []
            psnStats.value = {
                total_psn_games: data.stats.total_psn_games,
                matched_games: data.stats.matched_games,
                unmatched_games: data.stats.unmatched_games,
                has_guide: data.stats.has_guide,
            }
            psnHasGuideOnly.value = true
            applyPsnFilters()
        } catch (e) {
            psnError.value = e.message
        } finally {
            psnLoading.value = false
        }
    }

    function toggleHasGuideOnly() {
        psnHasGuideOnly.value = !psnHasGuideOnly.value
        applyPsnFilters()
    }

    function applyPsnFilters() {
        let gameIds = psnAllGameIds.value

        if (psnHasGuideOnly.value) {
            gameIds = psnHasGuideIds.value
        }

        psnGameIds.value = gameIds.length > 0 ? gameIds : null
    }

    function clearPSN() {
        psnUser.value = null
        psnStats.value = { total_psn_games: 0, matched_games: 0, unmatched_games: 0, has_guide: 0 }
        psnAllGameIds.value = []
        psnHasGuideIds.value = []
        psnUnmatchedTitles.value = []
        psnHasGuideOnly.value = true
        psnUsername.value = ''
        psnGameIds.value = null
    }

    return {
        // State (readonly for external use)
        psnUsername,
        psnLoading: readonly(psnLoading),
        psnError: readonly(psnError),
        psnUser: readonly(psnUser),
        psnStats: readonly(psnStats),
        psnAllGameIds: readonly(psnAllGameIds),
        psnHasGuideIds: readonly(psnHasGuideIds),
        psnHasGuideOnly: readonly(psnHasGuideOnly),
        psnUnmatchedTitles: readonly(psnUnmatchedTitles),
        psnGameIds: readonly(psnGameIds),

        // Computed
        psnFilteredCount,
        isPsnLoaded,
        isAdmin,
        isAuthenticated,

        // Actions
        loadMyLibrary,
        lookupPSN,
        toggleHasGuideOnly,
        clearPSN,
    }
}
