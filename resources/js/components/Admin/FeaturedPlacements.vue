<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Featured Placements</h1>
                <button @click="showAddModal = true" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium">
                    Add Placement
                </button>
            </div>

            <!-- Section Label -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section Label</label>
                <div class="flex items-center gap-3">
                    <input
                        v-model="sectionLabel"
                        type="text"
                        placeholder="Featured"
                        maxlength="50"
                        class="w-64 px-3 py-2 bg-gray-100 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm dark:text-gray-200"
                    />
                    <button
                        @click="saveLabel"
                        :disabled="savingLabel"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium disabled:opacity-50"
                    >
                        {{ savingLabel ? 'Saving...' : 'Save' }}
                    </button>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Displayed as the header above featured games</span>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Priority</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Game</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tagline</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">PSN</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dates</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Active</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Clicks</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Impressions</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <template v-for="placement in placements" :key="placement.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
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
                                    :value="placement.tagline"
                                    @change="updateField(placement, 'tagline', $event.target.value)"
                                    placeholder="Short tagline..."
                                    class="w-40 px-2 py-1 bg-gray-100 dark:bg-slate-700 border-0 rounded text-sm dark:text-gray-200"
                                    maxlength="120"
                                />
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    @click="togglePsnPanel(placement.id)"
                                    class="text-xs font-medium px-2 py-1 rounded"
                                    :class="placement.game?.psn_titles?.length ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400'"
                                >
                                    {{ placement.game?.psn_titles?.length ? `${placement.game.psn_titles.length} linked` : 'Link' }}
                                </button>
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
                            <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ placement.clicks_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ placement.impressions ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="removePlacement(placement)" class="text-red-500 hover:text-red-400 text-sm font-medium">
                                    Remove
                                </button>
                            </td>
                        </tr>
                        <!-- PSN Linking Panel -->
                        <tr v-if="expandedPsnPanel === placement.id" class="bg-gray-50 dark:bg-slate-700/20">
                            <td :colspan="9" class="px-6 py-4">
                                <div class="space-y-3">
                                    <!-- Trophy Status -->
                                    <div v-if="placement.game?.has_platinum || placement.game?.bronze_count" class="flex items-center gap-3 text-xs">
                                        <span class="text-gray-500 dark:text-gray-400">Trophies:</span>
                                        <span class="text-amber-700 dark:text-amber-500">{{ placement.game?.bronze_count ?? 0 }} Bronze</span>
                                        <span class="text-gray-400 dark:text-gray-300">{{ placement.game?.silver_count ?? 0 }} Silver</span>
                                        <span class="text-yellow-600 dark:text-yellow-400">{{ placement.game?.gold_count ?? 0 }} Gold</span>
                                        <span v-if="placement.game?.has_platinum" class="text-indigo-600 dark:text-indigo-400">Platinum</span>
                                    </div>
                                    <div v-else class="text-xs text-gray-400 dark:text-gray-500 italic">No trophy data — link a PSN title below to sync</div>

                                    <!-- PSN Store URL -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">PS Store URL:</span>
                                        <input
                                            type="url"
                                            :value="placement.game?.psn_store_url || ''"
                                            @change="updateGamePsnStoreUrl(placement, $event.target.value)"
                                            placeholder="https://store.playstation.com/..."
                                            class="flex-1 max-w-md px-2 py-1 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded text-xs dark:text-gray-200"
                                        />
                                    </div>

                                    <!-- Linked PSN Titles -->
                                    <div v-if="placement.game?.psn_titles?.length" class="space-y-1">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Linked PSN Titles:</span>
                                        <div v-for="psnTitle in placement.game.psn_titles" :key="psnTitle.id" class="flex items-center gap-2 text-xs bg-white dark:bg-slate-800 rounded px-3 py-1.5 border border-gray-200 dark:border-slate-600">
                                            <span class="text-gray-900 dark:text-white font-medium">{{ psnTitle.psn_title }}</span>
                                            <span class="text-gray-400">({{ psnTitle.platform || '?' }})</span>
                                            <span class="text-gray-400 font-mono">{{ psnTitle.np_communication_id }}</span>
                                            <button @click="unlinkPsn(placement, psnTitle.id)" class="ml-auto text-red-500 hover:text-red-400 font-medium">Unlink</button>
                                        </div>
                                    </div>

                                    <!-- Manual NP ID -->
                                    <div>
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Add NP Communication ID:</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <input
                                                v-model="manualNpId"
                                                type="text"
                                                placeholder="NPWR12345_00"
                                                maxlength="14"
                                                class="w-40 px-2 py-1 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded text-xs font-mono dark:text-gray-200"
                                            />
                                            <button
                                                @click="addManualNpId(placement)"
                                                :disabled="!manualNpId || addingNpId"
                                                class="px-3 py-1 bg-primary-600 text-white rounded text-xs font-medium hover:bg-primary-500 disabled:opacity-50"
                                            >
                                                {{ addingNpId ? 'Adding...' : 'Add' }}
                                            </button>
                                            <span v-if="npIdError" class="text-xs text-red-500">{{ npIdError }}</span>
                                        </div>
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 block">Format: NPWR#####_## — creates the record and links it to this game</span>
                                    </div>

                                    <!-- Search PSN Titles -->
                                    <div>
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Search existing unmatched PSN titles:</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <input
                                                v-model="psnSearch"
                                                type="text"
                                                placeholder="Search by game name..."
                                                @input="debouncedSearchPsn"
                                                class="flex-1 max-w-sm px-2 py-1 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded text-xs dark:text-gray-200"
                                            />
                                        </div>
                                        <div v-if="psnResults.length > 0" class="mt-2 max-h-40 overflow-y-auto bg-white dark:bg-slate-800 rounded border border-gray-200 dark:border-slate-600">
                                            <button
                                                v-for="title in psnResults"
                                                :key="title.id"
                                                @click="!title.game_id && linkPsn(placement, title)"
                                                class="w-full flex items-center gap-3 px-3 py-2 text-left text-xs transition-colors"
                                                :class="title.game_id ? 'opacity-60 cursor-default' : 'hover:bg-gray-50 dark:hover:bg-slate-700'"
                                            >
                                                <span class="font-medium text-gray-900 dark:text-white">{{ title.psn_title }}</span>
                                                <span class="text-gray-400">({{ title.platform || '?' }})</span>
                                                <span class="text-gray-400 font-mono text-[10px]">{{ title.np_communication_id }}</span>
                                                <span v-if="title.game_id" class="ml-auto text-green-600 dark:text-green-400 font-medium">already linked</span>
                                                <span v-else class="ml-auto text-gray-400">seen {{ title.times_seen }}x</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        </template>
                        <tr v-if="placements.length === 0 && !loading">
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
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

                    <!-- Tagline -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tagline</label>
                        <input
                            v-model="newTagline"
                            type="text"
                            placeholder="A short description for sponsors..."
                            maxlength="120"
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
const sectionLabel = ref('Featured')
const savingLabel = ref(false)

// Add form
const gameSearch = ref('')
const gameResults = ref([])
const selectedGame = ref(null)
const newTagline = ref('')
const newStartsAt = ref('')
const newEndsAt = ref('')

// PSN linking
const expandedPsnPanel = ref(null)
const psnSearch = ref('')
const psnResults = ref([])
const manualNpId = ref('')
const addingNpId = ref(false)
const npIdError = ref('')

let searchTimeout = null
let psnSearchTimeout = null

async function fetchPlacements() {
    loading.value = true
    try {
        const response = await fetch('/api/admin/featured-placements', { credentials: 'include' })
        if (!response.ok) throw new Error('Failed to load')
        const data = await response.json()
        sectionLabel.value = data.label || 'Featured'
        placements.value = data.placements || []
    } catch (e) {
        console.error('Error loading featured placements:', e)
    } finally {
        loading.value = false
    }
}

async function saveLabel() {
    savingLabel.value = true
    try {
        const response = await fetch('/api/admin/featured-placements/label', {
            method: 'PUT',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ label: sectionLabel.value }),
        })
        if (!response.ok) throw new Error('Failed to save label')
    } catch (e) {
        console.error('Error saving label:', e)
    } finally {
        savingLabel.value = false
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
            tagline: newTagline.value || null,
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
        newTagline.value = ''
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

function togglePsnPanel(placementId) {
    if (expandedPsnPanel.value === placementId) {
        expandedPsnPanel.value = null
    } else {
        expandedPsnPanel.value = placementId
        psnSearch.value = ''
        psnResults.value = []
        manualNpId.value = ''
        npIdError.value = ''
    }
}

async function addManualNpId(placement) {
    if (!manualNpId.value) return
    addingNpId.value = true
    npIdError.value = ''
    try {
        const response = await fetch(`/api/admin/featured-placements/${placement.id}/add-np-id`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ np_communication_id: manualNpId.value.trim() }),
        })
        const data = await response.json()
        if (!response.ok) {
            npIdError.value = data.message || 'Failed to add'
            return
        }
        const idx = placements.value.findIndex(p => p.id === placement.id)
        if (idx !== -1) {
            placements.value[idx].game = data.game
        }
        manualNpId.value = ''
    } catch (e) {
        console.error('Error adding NP ID:', e)
        npIdError.value = 'Failed to add'
    } finally {
        addingNpId.value = false
    }
}

function debouncedSearchPsn() {
    clearTimeout(psnSearchTimeout)
    psnSearchTimeout = setTimeout(() => searchPsnTitles(), 300)
}

async function searchPsnTitles() {
    if (psnSearch.value.length < 2) {
        psnResults.value = []
        return
    }
    try {
        const params = new URLSearchParams({ q: psnSearch.value })
        const response = await fetch(`/api/admin/featured-placements/search-psn?${params}`, { credentials: 'include' })
        if (!response.ok) throw new Error('Search failed')
        psnResults.value = await response.json()
    } catch (e) {
        console.error('Error searching PSN titles:', e)
        psnResults.value = []
    }
}

async function linkPsn(placement, title) {
    try {
        const response = await fetch(`/api/admin/featured-placements/${placement.id}/link-psn`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ psn_title_id: title.id }),
        })
        if (!response.ok) throw new Error('Link failed')
        const data = await response.json()
        // Update the placement's game data in-place
        const idx = placements.value.findIndex(p => p.id === placement.id)
        if (idx !== -1) {
            placements.value[idx].game = data.game
        }
        psnSearch.value = ''
        psnResults.value = []
    } catch (e) {
        console.error('Error linking PSN title:', e)
    }
}

async function unlinkPsn(placement, psnTitleId) {
    try {
        const response = await fetch(`/api/admin/featured-placements/${placement.id}/unlink-psn`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ psn_title_id: psnTitleId }),
        })
        if (!response.ok) throw new Error('Unlink failed')
        const data = await response.json()
        const idx = placements.value.findIndex(p => p.id === placement.id)
        if (idx !== -1) {
            placements.value[idx].game = data.game
        }
    } catch (e) {
        console.error('Error unlinking PSN title:', e)
    }
}

async function updateGamePsnStoreUrl(placement, url) {
    try {
        const gameId = placement.game?.id
        if (!gameId) return
        const response = await fetch(`/api/admin/games/${gameId}`, {
            method: 'PUT',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ psn_store_url: url || null }),
        })
        if (!response.ok) throw new Error('Update failed')
        // Update local state
        const idx = placements.value.findIndex(p => p.id === placement.id)
        if (idx !== -1 && placements.value[idx].game) {
            placements.value[idx].game.psn_store_url = url || null
        }
    } catch (e) {
        console.error('Error updating PSN store URL:', e)
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
