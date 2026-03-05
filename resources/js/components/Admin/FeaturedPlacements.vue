<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Featured Placements</h1>
                <button @click="showAddModal = true" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium">
                    Add Placement
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pos</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Game</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Label</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dates</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Active</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <tr v-for="placement in placements" :key="placement.id" class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                <input
                                    type="number"
                                    :value="placement.position"
                                    @change="updatePosition(placement, $event.target.value)"
                                    class="w-14 px-2 py-1 bg-gray-100 dark:bg-slate-700 border-0 rounded text-sm text-center"
                                    min="0"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="placement.game?.cover_url"
                                        :src="placement.game.cover_url"
                                        :alt="placement.game.title"
                                        class="w-10 h-14 rounded object-cover ring-1 ring-gray-200 dark:ring-slate-600"
                                    />
                                    <div v-else class="w-10 h-14 rounded bg-gray-200 dark:bg-slate-700 shrink-0"></div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ placement.game?.title }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <input
                                    type="text"
                                    :value="placement.label"
                                    @change="updateField(placement, 'label', $event.target.value)"
                                    class="w-32 px-2 py-1 bg-gray-100 dark:bg-slate-700 border-0 rounded text-sm dark:text-gray-200"
                                />
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400">From:</span>
                                        <input
                                            type="date"
                                            :value="formatDateInput(placement.starts_at)"
                                            @change="updateField(placement, 'starts_at', $event.target.value || null)"
                                            class="px-1.5 py-0.5 bg-gray-100 dark:bg-slate-700 border-0 rounded text-xs dark:text-gray-300"
                                        />
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400">Until:</span>
                                        <input
                                            type="date"
                                            :value="formatDateInput(placement.ends_at)"
                                            @change="updateField(placement, 'ends_at', $event.target.value || null)"
                                            class="px-1.5 py-0.5 bg-gray-100 dark:bg-slate-700 border-0 rounded text-xs dark:text-gray-300"
                                        />
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    @click="toggleActive(placement)"
                                    :class="[
                                        'relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                                        placement.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-slate-600'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'inline-block h-4 w-4 rounded-full bg-white shadow transition-transform',
                                            placement.is_active ? 'translate-x-[18px]' : 'translate-x-0.5'
                                        ]"
                                    />
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="removePlacement(placement)" class="text-red-500 hover:text-red-400 text-sm font-medium">
                                    Remove
                                </button>
                            </td>
                        </tr>
                        <tr v-if="placements.length === 0 && !loading">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No featured placements yet. Click "Add Placement" to feature a game.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Placement Modal -->
        <Transition name="modal">
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showAddModal = false">
                <div class="fixed inset-0 bg-black/50" @click="showAddModal = false"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Add Featured Placement</h2>

                    <!-- Game Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Game</label>
                        <input
                            v-model="gameSearch"
                            type="text"
                            placeholder="Type to search..."
                            @input="debouncedSearchGames"
                            class="w-full px-3 py-2 bg-gray-100 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm dark:text-gray-200"
                        />
                        <div v-if="gameResults.length > 0" class="mt-2 max-h-48 overflow-y-auto bg-gray-50 dark:bg-slate-700 rounded-lg border border-gray-200 dark:border-slate-600">
                            <button
                                v-for="game in gameResults"
                                :key="game.id"
                                @click="selectGame(game)"
                                class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-100 dark:hover:bg-slate-600 text-left transition-colors"
                            >
                                <img v-if="game.cover_url" :src="game.cover_url" class="w-8 h-10 rounded object-cover" />
                                <div v-else class="w-8 h-10 rounded bg-gray-300 dark:bg-slate-500 shrink-0"></div>
                                <span class="text-sm text-gray-900 dark:text-white truncate">{{ game.title }}</span>
                            </button>
                        </div>
                        <div v-if="selectedGame" class="mt-2 flex items-center gap-2 px-3 py-2 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
                            <img v-if="selectedGame.cover_url" :src="selectedGame.cover_url" class="w-8 h-10 rounded object-cover" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedGame.title }}</span>
                            <button @click="selectedGame = null" class="ml-auto text-gray-400 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Label -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                        <input
                            v-model="newLabel"
                            type="text"
                            placeholder="Indie Spotlight"
                            class="w-full px-3 py-2 bg-gray-100 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm dark:text-gray-200"
                        />
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Starts At</label>
                            <input
                                v-model="newStartsAt"
                                type="date"
                                class="w-full px-3 py-2 bg-gray-100 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm dark:text-gray-200"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ends At</label>
                            <input
                                v-model="newEndsAt"
                                type="date"
                                class="w-full px-3 py-2 bg-gray-100 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm dark:text-gray-200"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showAddModal = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                            Cancel
                        </button>
                        <button
                            @click="addPlacement"
                            :disabled="!selectedGame || saving"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium disabled:opacity-50"
                        >
                            {{ saving ? 'Adding...' : 'Add' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from './AdminLayout.vue'

const placements = ref([])
const loading = ref(false)
const saving = ref(false)
const showAddModal = ref(false)

// Add form
const gameSearch = ref('')
const gameResults = ref([])
const selectedGame = ref(null)
const newLabel = ref('Indie Spotlight')
const newStartsAt = ref('')
const newEndsAt = ref('')

let searchTimeout = null

async function fetchPlacements() {
    loading.value = true
    try {
        const response = await fetch('/api/admin/featured-placements', { credentials: 'include' })
        if (!response.ok) throw new Error('Failed to load')
        placements.value = await response.json()
    } catch (e) {
        console.error('Error loading featured placements:', e)
    } finally {
        loading.value = false
    }
}

function debouncedSearchGames() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => searchGames(), 300)
}

async function searchGames() {
    if (gameSearch.value.length < 2) {
        gameResults.value = []
        return
    }
    try {
        const params = new URLSearchParams({ search: gameSearch.value, per_page: '10' })
        const response = await fetch(`/api/admin/games?${params}`, { credentials: 'include' })
        if (!response.ok) throw new Error('Search failed')
        const data = await response.json()
        gameResults.value = (data.data || []).map(g => ({ id: g.id, title: g.title, cover_url: g.cover_url }))
    } catch (e) {
        console.error('Error searching games:', e)
        gameResults.value = []
    }
}

function selectGame(game) {
    selectedGame.value = game
    gameSearch.value = ''
    gameResults.value = []
}

async function addPlacement() {
    if (!selectedGame.value) return
    saving.value = true
    try {
        const body = {
            game_id: selectedGame.value.id,
            label: newLabel.value || 'Indie Spotlight',
        }
        if (newStartsAt.value) body.starts_at = newStartsAt.value
        if (newEndsAt.value) body.ends_at = newEndsAt.value

        const response = await fetch('/api/admin/featured-placements', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body),
        })
        if (!response.ok) throw new Error('Failed to add')

        showAddModal.value = false
        selectedGame.value = null
        gameSearch.value = ''
        newLabel.value = 'Indie Spotlight'
        newStartsAt.value = ''
        newEndsAt.value = ''
        await fetchPlacements()
    } catch (e) {
        console.error('Error adding placement:', e)
    } finally {
        saving.value = false
    }
}

async function updateField(placement, field, value) {
    try {
        const response = await fetch(`/api/admin/featured-placements/${placement.id}`, {
            method: 'PUT',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ [field]: value }),
        })
        if (!response.ok) throw new Error('Update failed')
        const updated = await response.json()
        const idx = placements.value.findIndex(p => p.id === placement.id)
        if (idx !== -1) placements.value[idx] = updated
    } catch (e) {
        console.error('Error updating placement:', e)
    }
}

function updatePosition(placement, value) {
    updateField(placement, 'position', parseInt(value, 10))
}

async function toggleActive(placement) {
    await updateField(placement, 'is_active', !placement.is_active)
}

async function removePlacement(placement) {
    if (!confirm(`Remove "${placement.game?.title}" from featured placements?`)) return
    try {
        const response = await fetch(`/api/admin/featured-placements/${placement.id}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
        })
        if (!response.ok) throw new Error('Delete failed')
        placements.value = placements.value.filter(p => p.id !== placement.id)
    } catch (e) {
        console.error('Error removing placement:', e)
    }
}

function formatDateInput(dateStr) {
    if (!dateStr) return ''
    return dateStr.substring(0, 10)
}

onMounted(() => {
    fetchPlacements()
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.2s ease-out;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
