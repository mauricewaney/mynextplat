<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">NP ID Management</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Link PSN NP Communication IDs to games for reliable matching
                    </p>
                </div>
                <button
                    @click="loadData"
                    :disabled="loading"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                >
                    <span v-if="loading">Loading...</span>
                    <span v-else>Refresh</span>
                </button>
            </div>

            <!-- Collect from PSN User -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Collect NP IDs from PSN User</h3>
                <div class="flex gap-2">
                    <input
                        v-model="collectUsername"
                        @keyup.enter="collectFromUser"
                        type="text"
                        placeholder="Enter PSN username..."
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :disabled="collecting"
                    />
                    <button
                        @click="collectFromUser"
                        :disabled="collecting || !collectUsername.trim()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors whitespace-nowrap"
                    >
                        <span v-if="collecting">Collecting...</span>
                        <span v-else>Collect NP IDs</span>
                    </button>
                    <button
                        @click="autoMatchAll"
                        :disabled="autoMatching"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors whitespace-nowrap"
                    >
                        <span v-if="autoMatching">Matching...</span>
                        <span v-else>Auto-match 100%</span>
                    </button>
                </div>
                <p v-if="collectResult" class="mt-2 text-sm" :class="collectResult.success ? 'text-green-600' : 'text-red-600'">
                    {{ collectResult.message }}
                </p>
            </div>

            <!-- Stats -->
            <div v-if="stats" class="grid grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ stats.total_titles }}</div>
                    <div class="text-sm text-gray-500">Total PSN titles</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-green-600">{{ stats.matched_titles }}</div>
                    <div class="text-sm text-gray-500">Matched</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-orange-600">{{ stats.unmatched_titles }}</div>
                    <div class="text-sm text-gray-500">Unmatched</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-indigo-600">
                        {{ stats.total_titles > 0 ? Math.round((stats.matched_titles / stats.total_titles) * 100) : 0 }}%
                    </div>
                    <div class="text-sm text-gray-500">Match rate</div>
                </div>
            </div>

            <!-- Skipped indicator -->
            <div v-if="skipCount > 0" class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 flex items-center justify-between">
                <span class="text-sm text-gray-600">
                    {{ skipCount }} item{{ skipCount === 1 ? '' : 's' }} skipped (shown at bottom)
                </span>
                <button
                    @click="clearSkipped()"
                    class="text-sm text-indigo-600 hover:text-indigo-700 underline"
                >
                    Clear all skips
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="searchQuery"
                            @input="debouncedLoad"
                            type="text"
                            placeholder="Search PSN titles..."
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        />
                    </div>
                    <select
                        v-model="selectedPlatform"
                        @change="loadUnmatched"
                        class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">All Platforms</option>
                        <option value="PS5">PS5</option>
                        <option value="PS4">PS4</option>
                        <option value="PS3">PS3</option>
                        <option value="PSVITA">PS Vita</option>
                    </select>
                    <select
                        v-model="sortBy"
                        @change="loadUnmatched"
                        class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="times_seen">Most Popular</option>
                        <option value="title">Title A-Z</option>
                        <option value="created_at">Recently Added</option>
                    </select>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!loading && unmatched.length === 0" class="bg-white rounded-lg shadow p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No unmatched titles</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Use the input above to collect NP IDs from PSN users
                </p>
            </div>

            <!-- Unmatched List -->
            <div v-else class="space-y-4">
                <div
                    v-for="item in unmatched"
                    :key="item.id"
                    class="rounded-lg shadow p-4 transition-all"
                    :class="item.is_skipped ? 'bg-gray-100 opacity-60' : 'bg-white'"
                >
                    <div class="flex items-start gap-4">
                        <!-- Skip/Unskip button -->
                        <button
                            v-if="item.is_skipped"
                            @click="unskipItem(item)"
                            class="text-xs text-indigo-500 hover:text-indigo-700 px-2 py-1 hover:bg-indigo-50 rounded transition-colors shrink-0"
                            title="Move back to top"
                        >
                            Unskip
                        </button>
                        <button
                            v-else
                            @click="skipItem(item)"
                            class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1 hover:bg-gray-100 rounded transition-colors shrink-0"
                            title="Move to bottom of list"
                        >
                            Skip
                        </button>

                        <!-- Icon -->
                        <img
                            v-if="item.icon_url"
                            :src="item.icon_url"
                            class="w-12 h-12 rounded object-cover bg-gray-100"
                            @error="$event.target.style.display = 'none'"
                        />
                        <div v-else class="w-12 h-12 rounded bg-gray-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>

                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium text-gray-900">{{ item.psn_title || '(Empty title)' }}</span>
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-mono rounded">
                                    {{ item.np_communication_id }}
                                </span>
                                <span v-if="item.platform" class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">
                                    {{ item.platform }}
                                </span>
                            </div>

                            <!-- Trophy info -->
                            <div v-if="item.has_platinum || item.bronze_count" class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                                <span v-if="item.has_platinum" class="text-yellow-600">Platinum</span>
                                <span v-if="item.gold_count">{{ item.gold_count }} Gold</span>
                                <span v-if="item.silver_count">{{ item.silver_count }} Silver</span>
                                <span v-if="item.bronze_count">{{ item.bronze_count }} Bronze</span>
                            </div>

                            <!-- Discovered from -->
                            <div v-if="item.discovered_from" class="mt-1 text-xs text-gray-400">
                                First seen: {{ item.discovered_from }}
                            </div>

                            <!-- Search & Link -->
                            <div class="mt-3 space-y-3">
                                <!-- Auto Suggestions (preloaded) -->
                                <div v-if="item.suggestions?.length" class="mb-3">
                                    <div class="text-xs font-medium text-green-600 mb-2">Suggested matches:</div>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="suggestion in item.suggestions"
                                            :key="suggestion.id"
                                            @click="linkToGame(item, suggestion.id)"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm rounded transition-colors border"
                                            :class="suggestion.similarity >= 80
                                                ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100'
                                                : suggestion.similarity >= 60
                                                    ? 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100'
                                                    : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'"
                                        >
                                            <img v-if="suggestion.cover_url" :src="suggestion.cover_url" class="w-6 h-8 rounded object-cover" />
                                            <span>{{ suggestion.title }}</span>
                                            <span class="px-1.5 py-0.5 rounded text-xs font-medium"
                                                :class="suggestion.similarity >= 80
                                                    ? 'bg-green-200 text-green-800'
                                                    : suggestion.similarity >= 60
                                                        ? 'bg-yellow-200 text-yellow-800'
                                                        : 'bg-gray-200 text-gray-600'"
                                            >
                                                {{ suggestion.similarity }}%
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Local DB Search -->
                                <div>
                                    <label class="text-xs font-medium text-gray-500 mb-1 block">Search Local Database</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="item.searchQuery"
                                            @input="searchGame(item)"
                                            type="text"
                                            placeholder="Search your games..."
                                            class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                    </div>

                                    <!-- Local Search Results -->
                                    <div v-if="item.searchResults?.length" class="mt-2 flex flex-wrap gap-2">
                                        <button
                                            v-for="result in item.searchResults"
                                            :key="result.id"
                                            @click="linkToGame(item, result.id)"
                                            class="inline-flex items-center gap-2 px-2 py-1 bg-gray-50 text-gray-700 text-sm rounded hover:bg-green-50 hover:text-green-700 transition-colors border border-gray-200"
                                        >
                                            <img v-if="result.cover_url" :src="result.cover_url" class="w-5 h-5 rounded object-cover" />
                                            {{ result.title }}
                                        </button>
                                    </div>
                                </div>

                                <!-- IGDB Search -->
                                <div>
                                    <label class="text-xs font-medium text-purple-600 mb-1 block">Search IGDB (import new game)</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="item.igdbQuery"
                                            @input="searchIgdb(item)"
                                            type="text"
                                            :placeholder="'Search IGDB for ' + (item.psn_title || 'game') + '...'"
                                            class="flex-1 px-3 py-1.5 text-sm border border-purple-300 rounded focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        />
                                        <span v-if="item.igdbLoading" class="text-xs text-gray-400">Searching...</span>
                                    </div>

                                    <!-- IGDB Results -->
                                    <div v-if="item.igdbResults?.length" class="mt-2 flex flex-wrap gap-2">
                                        <button
                                            v-for="result in item.igdbResults"
                                            :key="result.igdb_id"
                                            @click="importAndLink(item, result)"
                                            :disabled="item.importing"
                                            class="inline-flex items-center gap-2 px-2 py-1 bg-purple-50 text-purple-700 text-sm rounded hover:bg-purple-100 transition-colors border border-purple-200"
                                        >
                                            <img v-if="result.cover_url" :src="result.cover_url" class="w-5 h-7 rounded object-cover" />
                                            <div class="text-left">
                                                <div>{{ result.title }}</div>
                                                <div class="text-xs text-purple-400">
                                                    {{ result.release_date ? result.release_date.substring(0, 4) : '' }}
                                                    {{ result.developer ? '• ' + result.developer : '' }}
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Create New Game -->
                                <div class="pt-2 border-t border-gray-200">
                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="!item.showCreateForm"
                                            @click="item.showCreateForm = true; item.newGameTitle = item.psn_title"
                                            class="text-xs text-orange-600 hover:text-orange-700 underline"
                                        >
                                            + Create new game manually
                                        </button>
                                        <template v-else>
                                            <input
                                                v-model="item.newGameTitle"
                                                type="text"
                                                placeholder="Game title..."
                                                class="flex-1 px-3 py-1.5 text-sm border border-orange-300 rounded focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                                @keyup.enter="createGame(item)"
                                            />
                                            <button
                                                @click="createGame(item)"
                                                :disabled="item.creating || !item.newGameTitle?.trim()"
                                                class="px-3 py-1.5 text-sm bg-orange-600 text-white rounded hover:bg-orange-700 disabled:opacity-50 transition-colors"
                                            >
                                                {{ item.creating ? '...' : 'Create' }}
                                            </button>
                                            <button
                                                @click="item.showCreateForm = false"
                                                class="px-2 py-1.5 text-sm text-gray-500 hover:text-gray-700"
                                            >
                                                Cancel
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2">
                    <button
                        @click="goToPage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <span class="px-3 py-1 text-sm text-gray-600">
                        Page {{ pagination.current_page }} of {{ pagination.last_page }}
                    </span>
                    <button
                        @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from './AdminLayout.vue'

const loading = ref(false)
const collecting = ref(false)
const autoMatching = ref(false)
const unmatched = ref([])
const stats = ref(null)
const pagination = ref(null)
const collectUsername = ref('')
const collectResult = ref(null)
const searchQuery = ref('')
const selectedPlatform = ref('')
const sortBy = ref('times_seen')
const currentPage = ref(1)
const skipCount = ref(0)

let searchTimeout = null
let loadTimeout = null

async function loadData() {
    await Promise.all([loadStats(), loadUnmatched()])
}

async function loadStats() {
    try {
        const response = await fetch('/api/admin/psn/stats')
        stats.value = await response.json()
    } catch (error) {
        console.error('Failed to load stats:', error)
    }
}

async function loadUnmatched() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page: currentPage.value,
            sort: sortBy.value,
            dir: sortBy.value === 'title' ? 'asc' : 'desc',
            per_page: 20
        })

        if (searchQuery.value) {
            params.append('search', searchQuery.value)
        }
        if (selectedPlatform.value) {
            params.append('platform', selectedPlatform.value)
        }

        const response = await fetch(`/api/admin/psn/unmatched?${params}`)
        const data = await response.json()

        unmatched.value = data.data.map(item => ({
            ...item,
            searchQuery: '',
            searchResults: [],
            // Keep suggestions from API response (preloaded with match probability)
            suggestions: item.suggestions || [],
            loadingSuggestions: false,
            igdbQuery: '',
            igdbResults: [],
            igdbLoading: false,
            importing: false,
            // Create game form
            showCreateForm: false,
            newGameTitle: '',
            creating: false,
            // Skip state
            skipping: false
        }))

        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            total: data.total
        }

        skipCount.value = data.skip_count || 0
    } catch (error) {
        console.error('Failed to load unmatched:', error)
    } finally {
        loading.value = false
    }
}

function debouncedLoad() {
    clearTimeout(loadTimeout)
    loadTimeout = setTimeout(() => {
        currentPage.value = 1
        loadUnmatched()
    }, 300)
}

function goToPage(page) {
    currentPage.value = page
    loadUnmatched()
}

async function collectFromUser() {
    if (!collectUsername.value.trim()) return

    collecting.value = true
    collectResult.value = null

    try {
        const response = await fetch(`/api/admin/psn/collect/${encodeURIComponent(collectUsername.value.trim())}`)
        const data = await response.json()

        collectResult.value = {
            success: data.success,
            message: data.success
                ? `Collected ${data.new_titles} new titles from ${data.username} (${data.existing_titles} already existed, ${data.auto_matched || 0} auto-matched)`
                : data.message
        }

        if (data.success) {
            collectUsername.value = ''
            await loadData()
        }
    } catch (error) {
        collectResult.value = {
            success: false,
            message: 'Failed to collect NP IDs'
        }
    } finally {
        collecting.value = false
    }
}

async function autoMatchAll() {
    autoMatching.value = true
    collectResult.value = null

    try {
        const response = await fetch('/api/admin/psn/auto-match-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        const data = await response.json()

        collectResult.value = {
            success: data.success,
            message: data.success
                ? `Auto-matched ${data.matched_count} titles. ${data.remaining_unmatched} still unmatched.`
                : data.message || 'Failed to auto-match'
        }

        if (data.success && data.matched_count > 0) {
            await loadData()
        }
    } catch (error) {
        collectResult.value = {
            success: false,
            message: 'Failed to run auto-match'
        }
    } finally {
        autoMatching.value = false
    }
}

async function createGame(item) {
    if (!item.newGameTitle?.trim()) return

    item.creating = true
    try {
        const response = await fetch('/api/admin/psn/create-game', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                psn_title_id: item.id,
                title: item.newGameTitle.trim()
            })
        })

        const data = await response.json()

        if (data.success) {
            // Remove from unmatched list
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            // Update stats
            if (stats.value) {
                stats.value.matched_titles++
                stats.value.unmatched_titles--
            }
        } else {
            alert(data.message || 'Failed to create game')
        }
    } catch (error) {
        console.error('Create failed:', error)
        alert('Failed to create game')
    } finally {
        item.creating = false
    }
}

async function skipItem(item) {
    item.skipping = true
    try {
        const response = await fetch('/api/admin/psn/skip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ psn_title_id: item.id })
        })

        if (response.ok) {
            item.is_skipped = true
            item.suggestions = [] // Clear suggestions for skipped items
            // Move to end of list
            unmatched.value = [
                ...unmatched.value.filter(u => u.id !== item.id && !u.is_skipped),
                ...unmatched.value.filter(u => u.is_skipped || u.id === item.id)
            ]
            skipCount.value++
        }
    } catch (error) {
        console.error('Skip failed:', error)
    } finally {
        item.skipping = false
    }
}

async function unskipItem(item) {
    item.skipping = true
    try {
        const response = await fetch('/api/admin/psn/unskip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ psn_title_id: item.id })
        })

        if (response.ok) {
            item.is_skipped = false
            skipCount.value--
            // Reload to get proper sorting and suggestions
            await loadUnmatched()
        }
    } catch (error) {
        console.error('Unskip failed:', error)
    } finally {
        item.skipping = false
    }
}

async function clearSkipped() {
    try {
        const response = await fetch('/api/admin/psn/clear-skips', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })

        if (response.ok) {
            skipCount.value = 0
            await loadUnmatched()
        }
    } catch (error) {
        console.error('Clear skips failed:', error)
    }
}

async function searchGame(item) {
    clearTimeout(searchTimeout)
    if (!item.searchQuery || item.searchQuery.length < 2) {
        item.searchResults = []
        return
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/api/admin/games/search-for-linking?query=${encodeURIComponent(item.searchQuery)}`)
            item.searchResults = await response.json()
        } catch (error) {
            console.error('Search failed:', error)
        }
    }, 300)
}

async function getSuggestions(item) {
    item.loadingSuggestions = true
    try {
        const response = await fetch(`/api/admin/psn/suggestions/${item.id}`)
        const data = await response.json()
        item.suggestions = data.suggestions
    } catch (error) {
        console.error('Failed to get suggestions:', error)
    } finally {
        item.loadingSuggestions = false
    }
}

let igdbTimeout = null

async function searchIgdb(item) {
    clearTimeout(igdbTimeout)
    if (!item.igdbQuery || item.igdbQuery.length < 2) {
        item.igdbResults = []
        return
    }

    igdbTimeout = setTimeout(async () => {
        item.igdbLoading = true
        try {
            const response = await fetch(`/api/admin/psn/search-igdb?query=${encodeURIComponent(item.igdbQuery)}`)
            const data = await response.json()
            item.igdbResults = data.results || []
        } catch (error) {
            console.error('IGDB search failed:', error)
            item.igdbResults = []
        } finally {
            item.igdbLoading = false
        }
    }, 400)
}

async function importAndLink(item, igdbGame) {
    item.importing = true
    try {
        const response = await fetch('/api/admin/psn/import-igdb-and-link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                psn_title_id: item.id,
                igdb_game: igdbGame
            })
        })

        const data = await response.json()

        if (data.success) {
            // Remove from unmatched list
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            // Update stats
            if (stats.value) {
                stats.value.matched_titles++
                stats.value.unmatched_titles--
            }
        } else {
            alert(data.message || 'Failed to import and link')
        }
    } catch (error) {
        console.error('Import failed:', error)
        alert('Failed to import game from IGDB')
    } finally {
        item.importing = false
    }
}

async function linkToGame(item, gameId) {
    try {
        const response = await fetch('/api/admin/psn/link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                psn_title_id: item.id,
                game_id: gameId
            })
        })

        const data = await response.json()

        if (data.success) {
            // Remove from unmatched list
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            // Update stats
            if (stats.value) {
                stats.value.matched_titles++
                stats.value.unmatched_titles--
            }
        } else {
            alert(data.message || 'Failed to link')
        }
    } catch (error) {
        console.error('Link failed:', error)
        alert('Failed to link')
    }
}

onMounted(() => {
    loadData()
})
</script>
