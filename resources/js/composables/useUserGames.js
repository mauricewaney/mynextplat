import { ref, reactive } from 'vue'
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api'

// Shared state across all components — loaded once, updated locally
const userGameMap = reactive({}) // { gameId: { status, preferred_guide } }
let mapLoaded = false
let mapLoading = null // Promise to avoid duplicate fetches

/**
 * Load all user's game IDs in one request (replaces N individual /check calls)
 */
async function loadUserGameIds() {
    if (mapLoaded) return
    if (mapLoading) return mapLoading

    mapLoading = (async () => {
        try {
            const data = await apiGet('/my-games/ids')
            Object.keys(userGameMap).forEach(k => delete userGameMap[k])
            Object.assign(userGameMap, data)
            mapLoaded = true
        } catch (e) {
            // Not authenticated or error — map stays empty
        } finally {
            mapLoading = null
        }
    })()

    return mapLoading
}

/**
 * Reset the shared cache (e.g., on logout)
 */
function resetUserGameMap() {
    Object.keys(userGameMap).forEach(k => delete userGameMap[k])
    mapLoaded = false
    mapLoading = null
}

// Direct exports for use outside composable pattern (router, auth)
export { loadUserGameIds, resetUserGameMap }

export function useUserGames() {
    const games = ref([])
    const loading = ref(false)
    const error = ref(null)

    /**
     * Get the user's game list
     */
    async function getMyGames(status = null) {
        loading.value = true
        error.value = null

        try {
            const endpoint = status && status !== 'all'
                ? `/my-games?status=${status}`
                : '/my-games'
            games.value = await apiGet(endpoint)
        } catch (e) {
            console.error('Failed to fetch games:', e)
            error.value = e.message
        } finally {
            loading.value = false
        }

        return games.value
    }

    /**
     * Add a game to the user's list
     */
    async function addToList(gameId, status = 'backlog') {
        try {
            const response = await apiPost('/my-games', { game_id: gameId, status })
            if (!response.ok) {
                const data = await response.json()
                throw new Error(data.message || 'Failed to add game')
            }
            // Update local cache
            userGameMap[gameId] = { status, preferred_guide: null }
            return true
        } catch (e) {
            console.error('Failed to add game:', e)
            throw e
        }
    }

    /**
     * Remove a game from the user's list
     */
    async function removeFromList(gameId) {
        try {
            const response = await apiDelete(`/my-games/${gameId}`)
            if (!response.ok) {
                const data = await response.json()
                throw new Error(data.message || 'Failed to remove game')
            }
            // Update local state
            games.value = games.value.filter(g => g.id !== gameId)
            delete userGameMap[gameId]
            return true
        } catch (e) {
            console.error('Failed to remove game:', e)
            throw e
        }
    }

    /**
     * Update a game's status in the user's list
     */
    async function updateStatus(gameId, status, notes = undefined) {
        try {
            const data = { status }
            if (notes !== undefined) {
                data.notes = notes
            }
            // apiPut returns parsed JSON on success, throws on error
            await apiPut(`/my-games/${gameId}`, data)
            // Update local state
            const game = games.value.find(g => g.id === gameId)
            if (game) {
                game.user_status = status
                if (notes !== undefined) {
                    game.notes = notes
                }
            }
            // Update shared cache
            if (userGameMap[gameId]) {
                userGameMap[gameId].status = status
            }
            return true
        } catch (e) {
            console.error('Failed to update game:', e)
            throw e
        }
    }

    /**
     * Check if a game is in the user's list (reads from local cache, no API call)
     */
    function checkInList(gameId) {
        const entry = userGameMap[gameId]
        if (!entry) {
            return { in_list: false }
        }
        return {
            in_list: true,
            status: entry.status,
            preferred_guide: entry.preferred_guide,
        }
    }

    /**
     * Update preferred guide for a game
     */
    async function updatePreferredGuide(gameId, guide) {
        try {
            const result = await apiPut(`/my-games/${gameId}`, { preferred_guide: guide })
            if (userGameMap[gameId]) {
                userGameMap[gameId].preferred_guide = guide
            }
            return result
        } catch (e) {
            console.error('Failed to update preferred guide:', e)
            throw e
        }
    }

    /**
     * Bulk add games to the user's list (merges with existing)
     */
    async function bulkAddToList(gameIds, status = 'backlog') {
        try {
            const response = await apiPost('/my-games/bulk', { game_ids: gameIds, status })
            if (!response.ok) {
                const data = await response.json()
                throw new Error(data.message || 'Failed to add games')
            }
            // Update local cache
            gameIds.forEach(id => {
                userGameMap[id] = { status, preferred_guide: null }
            })
            return await response.json()
        } catch (e) {
            console.error('Failed to bulk add games:', e)
            throw e
        }
    }

    return {
        games,
        loading,
        error,
        userGameMap,
        getMyGames,
        loadUserGameIds,
        resetUserGameMap,
        addToList,
        removeFromList,
        updateStatus,
        checkInList,
        bulkAddToList,
        updatePreferredGuide,
    }
}
