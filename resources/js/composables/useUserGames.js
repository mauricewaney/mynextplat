import { ref } from 'vue'
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api'

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
            return true
        } catch (e) {
            console.error('Failed to update game:', e)
            throw e
        }
    }

    /**
     * Check if a game is in the user's list
     */
    async function checkInList(gameId) {
        try {
            return await apiGet(`/my-games/${gameId}/check`)
        } catch (e) {
            console.error('Failed to check game:', e)
            return { in_list: false }
        }
    }

    /**
     * Update preferred guide for a game
     */
    async function updatePreferredGuide(gameId, guide) {
        try {
            return await apiPut(`/my-games/${gameId}`, { preferred_guide: guide })
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
        getMyGames,
        addToList,
        removeFromList,
        updateStatus,
        checkInList,
        bulkAddToList,
        updatePreferredGuide,
    }
}
