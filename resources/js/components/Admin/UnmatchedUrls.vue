<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Unmatched Trophy URLs</h1>
                    <p class="text-gray-600 text-sm">Hover over search results to preview, click to match</p>
                </div>
                <div class="flex items-center gap-4">
                    <select v-model="sourceFilter" class="border-gray-300 rounded-md shadow-sm text-sm" @change="page = 1">
                        <option value="">All Sources</option>
                        <option value="psnprofiles">PSNP</option>
                        <option value="playstationtrophies">PST</option>
                    </select>
                    <div class="text-sm text-gray-500">
                        {{ filtered.length }} unmatched
                    </div>
                    <div v-if="stats.dlc_count" class="text-sm text-yellow-600">
                        {{ stats.dlc_count }} DLC (hidden)
                    </div>
                </div>
            </div>

            <!-- Stats (compact) -->
            <div class="grid grid-cols-4 gap-2">
                <div class="bg-white rounded-lg shadow p-3 text-center">
                    <div class="text-xl font-bold text-red-600">{{ stats.psnprofiles_unmatched || 0 }}</div>
                    <div class="text-xs text-gray-500">PSNP Unmatched</div>
                </div>
                <div class="bg-white rounded-lg shadow p-3 text-center">
                    <div class="text-xl font-bold text-green-600">{{ stats.psnprofiles_matched || 0 }}</div>
                    <div class="text-xs text-gray-500">PSNP Matched</div>
                </div>
                <div class="bg-white rounded-lg shadow p-3 text-center">
                    <div class="text-xl font-bold text-red-600">{{ stats.pst_unmatched || 0 }}</div>
                    <div class="text-xs text-gray-500">PST Unmatched</div>
                </div>
                <div class="bg-white rounded-lg shadow p-3 text-center">
                    <div class="text-xl font-bold text-green-600">{{ stats.pst_matched || 0 }}</div>
                    <div class="text-xs text-gray-500">PST Matched</div>
                </div>
            </div>

            <!-- Main Content: Split View -->
            <div class="flex gap-4">
                <!-- Left: Preview Panel -->
                <div class="w-96 shrink-0 sticky top-4 self-start">
                    <!-- Game Preview -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Game Preview</h3>

                        <div v-if="previewGame" class="space-y-3">
                            <!-- Cover -->
                            <div class="flex gap-3">
                                <img
                                    v-if="previewGame.cover_url"
                                    :src="previewGame.cover_url"
                                    class="w-20 h-28 object-cover rounded shadow"
                                />
                                <div v-else class="w-20 h-28 bg-gray-200 rounded flex items-center justify-center text-gray-400">
                                    No Image
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-900 text-sm leading-tight">{{ previewGame.title }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">{{ previewGame.slug }}</p>
                                    <p v-if="previewGame.release_date" class="text-xs text-gray-400 mt-1">
                                        {{ formatDate(previewGame.release_date) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Platforms -->
                            <div v-if="previewGame.platforms?.length" class="flex flex-wrap gap-1">
                                <span
                                    v-for="p in previewGame.platforms"
                                    :key="p.id"
                                    class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-xs"
                                >
                                    {{ p.short_name || p.name }}
                                </span>
                            </div>

                            <!-- Existing Guides -->
                            <div class="border-t pt-3">
                                <p class="text-xs font-medium text-gray-500 mb-2">Existing Guides:</p>
                                <div class="space-y-1 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span :class="previewGame.psnprofiles_url ? 'text-green-600' : 'text-gray-300'">
                                            {{ previewGame.psnprofiles_url ? '✓' : '✗' }}
                                        </span>
                                        <span :class="previewGame.psnprofiles_url ? 'text-gray-700' : 'text-gray-400'">PSNP</span>
                                        <a v-if="previewGame.psnprofiles_url" :href="previewGame.psnprofiles_url" target="_blank" class="text-blue-500 truncate flex-1">
                                            {{ previewGame.psnprofiles_url.split('/').pop() }}
                                        </a>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span :class="previewGame.playstationtrophies_url ? 'text-green-600' : 'text-gray-300'">
                                            {{ previewGame.playstationtrophies_url ? '✓' : '✗' }}
                                        </span>
                                        <span :class="previewGame.playstationtrophies_url ? 'text-gray-700' : 'text-gray-400'">PST</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span :class="previewGame.powerpyx_url ? 'text-green-600' : 'text-gray-300'">
                                            {{ previewGame.powerpyx_url ? '✓' : '✗' }}
                                        </span>
                                        <span :class="previewGame.powerpyx_url ? 'text-gray-700' : 'text-gray-400'">PPX</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Match Button -->
                            <button
                                v-if="activeItem"
                                @click="matchToGame(activeItem, previewGame)"
                                class="w-full py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium"
                            >
                                Match to this Game
                            </button>
                        </div>

                        <div v-else class="text-center py-8 text-gray-400 text-sm">
                            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search for a game and hover to preview
                        </div>
                    </div>
                </div>

                <!-- Right: URL List -->
                <div class="flex-1 min-w-0">
                    <!-- Search -->
                    <div class="bg-white rounded-lg shadow p-3 mb-4">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Filter unmatched URLs..."
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <!-- List -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div v-if="loading" class="p-8 text-center text-gray-500">Loading...</div>
                        <div v-else-if="filtered.length === 0" class="p-8 text-center text-gray-500">
                            No unmatched URLs found
                        </div>
                        <div v-else class="divide-y divide-gray-100">
                            <div
                                v-for="item in paginated"
                                :key="item.id"
                                class="p-3 hover:bg-gray-50"
                                :class="{ 'bg-indigo-50': activeItem?.id === item.id }"
                            >
                                <!-- URL Info Row -->
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        :class="item.source === 'psnprofiles' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'"
                                        class="px-1.5 py-0.5 rounded text-xs font-medium shrink-0"
                                    >
                                        {{ item.source === 'psnprofiles' ? 'PSNP' : 'PST' }}
                                    </span>
                                    <span class="font-medium text-gray-900 text-sm truncate">{{ item.extracted_title }}</span>
                                    <button
                                        @click="markAsDlc(item)"
                                        class="ml-auto px-1.5 py-0.5 rounded text-xs font-medium shrink-0 bg-gray-100 text-gray-400 hover:bg-yellow-100 hover:text-yellow-700"
                                        title="Mark as DLC (moves to DLC list)"
                                    >
                                        DLC
                                    </button>
                                    <button
                                        @click="skipUrl(item)"
                                        class="p-1 text-gray-300 hover:text-red-500 shrink-0"
                                        title="Delete URL"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- URL Link -->
                                <a
                                    :href="item.url"
                                    target="_blank"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 truncate block mb-2"
                                >
                                    {{ item.url }}
                                </a>

                                <!-- Search Inputs -->
                                <div class="flex gap-2">
                                    <!-- DB Game Search -->
                                    <div class="relative flex-1">
                                        <input
                                            v-model="item.gameSearch"
                                            type="text"
                                            placeholder="Search DB..."
                                            class="w-full border-gray-300 rounded text-sm py-1.5 px-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            @focus="setActiveItem(item); item.searchMode = 'db'"
                                            @input="searchGames(item)"
                                        />
                                        <!-- DB Dropdown -->
                                        <div
                                            v-if="activeItem?.id === item.id && item.searchMode === 'db' && item.gameResults?.length > 0"
                                            class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-64 overflow-y-auto"
                                        >
                                            <button
                                                v-for="game in item.gameResults"
                                                :key="game.id"
                                                @mouseenter="previewGame = game"
                                                @click="matchToGame(item, game)"
                                                class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 flex items-center gap-2 border-b border-gray-50 last:border-0"
                                            >
                                                <img
                                                    v-if="game.cover_url"
                                                    :src="game.cover_url"
                                                    class="w-10 h-14 object-cover rounded shrink-0"
                                                />
                                                <div class="w-10 h-14 bg-gray-100 rounded flex items-center justify-center text-gray-400 shrink-0" v-else>
                                                    ?
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-medium text-gray-900">{{ game.title }}</div>
                                                    <div class="text-xs text-gray-400">{{ game.slug }}</div>
                                                    <div v-if="game.release_date" class="text-xs text-gray-400">{{ formatDate(game.release_date) }}</div>
                                                </div>
                                                <div class="flex flex-col gap-0.5 text-xs shrink-0">
                                                    <span :class="game.psnprofiles_url ? 'text-green-500' : 'text-gray-300'">PSNP</span>
                                                    <span :class="game.playstationtrophies_url ? 'text-green-500' : 'text-gray-300'">PST</span>
                                                    <span :class="game.powerpyx_url ? 'text-green-500' : 'text-gray-300'">PPX</span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- IGDB Search -->
                                    <div class="relative flex-1">
                                        <input
                                            v-model="item.igdbSearch"
                                            type="text"
                                            placeholder="Search IGDB..."
                                            class="w-full border-gray-300 rounded text-sm py-1.5 px-2 focus:ring-orange-500 focus:border-orange-500 bg-orange-50"
                                            @focus="setActiveItem(item); item.searchMode = 'igdb'"
                                            @input="searchIgdb(item)"
                                        />
                                        <span v-if="item.igdbLoading" class="absolute right-2 top-1/2 -translate-y-1/2">
                                            <svg class="animate-spin h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                        <!-- IGDB Dropdown -->
                                        <div
                                            v-if="activeItem?.id === item.id && item.searchMode === 'igdb' && item.igdbResults?.length > 0"
                                            class="absolute z-20 w-full mt-1 bg-white border border-orange-200 rounded-md shadow-lg max-h-64 overflow-y-auto"
                                        >
                                            <button
                                                v-for="game in item.igdbResults"
                                                :key="game.igdb_id"
                                                @mouseenter="previewGame = game"
                                                @click="importFromIgdb(item, game)"
                                                class="w-full px-3 py-2 text-left text-sm hover:bg-orange-50 flex items-center gap-2 border-b border-gray-50 last:border-0"
                                            >
                                                <img
                                                    v-if="game.cover_url"
                                                    :src="game.cover_url"
                                                    class="w-10 h-14 object-cover rounded shrink-0"
                                                />
                                                <div class="w-10 h-14 bg-orange-100 rounded flex items-center justify-center text-orange-400 shrink-0" v-else>
                                                    ?
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-medium text-gray-900">{{ game.title }}</div>
                                                    <div class="text-xs text-gray-400">{{ game.slug }}</div>
                                                    <div v-if="game.release_date" class="text-xs text-gray-400">{{ formatDate(game.release_date) }}</div>
                                                </div>
                                                <div class="flex flex-wrap gap-0.5 text-xs shrink-0">
                                                    <span
                                                        v-for="p in (game.platforms_data || [])"
                                                        :key="p.slug"
                                                        class="px-1 py-0.5 bg-orange-100 text-orange-700 rounded text-xs"
                                                    >
                                                        {{ p.short_name }}
                                                    </span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="px-4 py-3 bg-gray-50 border-t flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                {{ (page - 1) * perPage + 1 }}-{{ Math.min(page * perPage, filtered.length) }} of {{ filtered.length }}
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="page = Math.max(1, page - 1)"
                                    :disabled="page === 1"
                                    class="px-3 py-1 border rounded text-sm disabled:opacity-50"
                                >
                                    Prev
                                </button>
                                <span class="text-sm">{{ page }}/{{ totalPages }}</span>
                                <button
                                    @click="page = Math.min(totalPages, page + 1)"
                                    :disabled="page === totalPages"
                                    class="px-3 py-1 border rounded text-sm disabled:opacity-50"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex gap-4 text-sm">
                <router-link to="/admin/games" class="text-indigo-600 hover:text-indigo-800">← Games</router-link>
                <router-link to="/admin/trophy-import" class="text-indigo-600 hover:text-indigo-800">Import URLs →</router-link>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import AdminLayout from './AdminLayout.vue'

const unmatched = ref([])
const loading = ref(true)
const search = ref('')
const sourceFilter = ref('')
const page = ref(1)
const perPage = 15
const stats = ref({})
const previewGame = ref(null)
const activeItem = ref(null)

const filtered = computed(() => {
    let items = unmatched.value

    if (sourceFilter.value) {
        items = items.filter(i => i.source === sourceFilter.value)
    }

    if (search.value) {
        const q = search.value.toLowerCase()
        items = items.filter(i =>
            i.extracted_title?.toLowerCase().includes(q) ||
            i.extracted_slug?.toLowerCase().includes(q)
        )
    }

    return items
})

const totalPages = computed(() => Math.ceil(filtered.value.length / perPage))

const paginated = computed(() => {
    const start = (page.value - 1) * perPage
    return filtered.value.slice(start, start + perPage)
})

watch([search, sourceFilter], () => {
    page.value = 1
})

async function markAsDlc(item) {
    try {
        const response = await fetch(`/api/admin/trophy-urls/${item.id}/toggle-dlc`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
            },
        })

        const result = await response.json()
        if (result.success && result.is_dlc) {
            // Remove from list and update stats
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            stats.value.dlc_count = (stats.value.dlc_count || 0) + 1
            if (item.source === 'psnprofiles') {
                stats.value.psnprofiles_unmatched--
            } else {
                stats.value.pst_unmatched--
            }
        }
    } catch (e) {
        console.error('Failed to mark as DLC:', e)
    }
}

function formatDate(dateStr) {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

function setActiveItem(item) {
    activeItem.value = item
    item.showDropdown = true
}

async function loadUnmatched() {
    loading.value = true
    try {
        const response = await fetch('/api/admin/trophy-urls/unmatched')
        const data = await response.json()
        unmatched.value = data.urls.map(u => ({
            ...u,
            gameSearch: '',
            gameResults: [],
            igdbSearch: '',
            igdbResults: [],
            igdbLoading: false,
            searchMode: 'db',
            showDropdown: false,
        }))
        stats.value = data.stats
    } catch (e) {
        console.error('Failed to load unmatched URLs:', e)
    } finally {
        loading.value = false
    }
}

let searchTimeout = null
async function searchGames(item) {
    clearTimeout(searchTimeout)
    if (!item.gameSearch || item.gameSearch.length < 2) {
        item.gameResults = []
        return
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/api/admin/games?search=${encodeURIComponent(item.gameSearch)}&per_page=15`)
            const data = await response.json()
            item.gameResults = data.data || []
        } catch (e) {
            console.error('Failed to search games:', e)
        }
    }, 300)
}

let igdbSearchTimeout = null
async function searchIgdb(item) {
    clearTimeout(igdbSearchTimeout)
    if (!item.igdbSearch || item.igdbSearch.length < 2) {
        item.igdbResults = []
        return
    }

    igdbSearchTimeout = setTimeout(async () => {
        item.igdbLoading = true
        try {
            const response = await fetch(`/api/admin/trophy-urls/search-igdb?query=${encodeURIComponent(item.igdbSearch)}`)
            const data = await response.json()
            item.igdbResults = data.games || []
        } catch (e) {
            console.error('Failed to search IGDB:', e)
        } finally {
            item.igdbLoading = false
        }
    }, 500)
}

async function importFromIgdb(item, game, force = false) {
    try {
        const response = await fetch(`/api/admin/trophy-urls/${item.id}/import-igdb`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                igdb_id: game.igdb_id,
                title: game.title,
                slug: game.slug,
                cover_url: game.cover_url,
                banner_url: game.banner_url,
                developer: game.developer,
                publisher: game.publisher,
                release_date: game.release_date,
                critic_score: game.critic_score,
                platforms_data: game.platforms_data,
                force,
            }),
        })

        const result = await response.json()

        // Handle confirmation needed for existing guide URL
        if (result.needs_confirmation) {
            const sourceName = item.source === 'psnprofiles' ? 'PSNP' : 'PST'
            if (confirm(`"${result.game_title}" already has a ${sourceName} guide:\n${result.existing_url}\n\nOverwrite with new URL?`)) {
                return importFromIgdb(item, game, true)
            }
            return
        }

        if (result.success) {
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            if (item.source === 'psnprofiles') {
                stats.value.psnprofiles_unmatched--
                stats.value.psnprofiles_matched++
            } else {
                stats.value.pst_unmatched--
                stats.value.pst_matched++
            }
            previewGame.value = null
            activeItem.value = null
        } else {
            alert('Failed to import: ' + (result.message || 'Unknown error'))
        }
    } catch (e) {
        console.error('Failed to import from IGDB:', e)
        alert('Failed to import from IGDB')
    }
}

function getExistingGuideUrl(game, source) {
    if (source === 'psnprofiles') return game.psnprofiles_url
    if (source === 'playstationtrophies') return game.playstationtrophies_url
    return null
}

async function matchToGame(item, game) {
    // Check if game already has a guide from the same source
    const existingUrl = getExistingGuideUrl(game, item.source)
    let force = false
    if (existingUrl) {
        const sourceName = item.source === 'psnprofiles' ? 'PSNP' : 'PST'
        if (!confirm(`This game already has a ${sourceName} guide:\n${existingUrl}\n\nOverwrite with new URL?`)) {
            return
        }
        force = true
    }

    try {
        const response = await fetch(`/api/admin/trophy-urls/${item.id}/match`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ game_id: game.id, force }),
        })

        if (response.ok) {
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            if (item.source === 'psnprofiles') {
                stats.value.psnprofiles_unmatched--
                stats.value.psnprofiles_matched++
            } else {
                stats.value.pst_unmatched--
                stats.value.pst_matched++
            }
            previewGame.value = null
            activeItem.value = null
        }
    } catch (e) {
        console.error('Failed to match:', e)
    }
}

async function skipUrl(item) {
    if (!confirm('Delete this URL? It won\'t be matched.')) return

    try {
        const response = await fetch(`/api/admin/trophy-urls/${item.id}`, {
            method: 'DELETE',
        })

        if (response.ok) {
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            if (item.source === 'psnprofiles') {
                stats.value.psnprofiles_unmatched--
            } else {
                stats.value.pst_unmatched--
            }
        }
    } catch (e) {
        console.error('Failed to delete:', e)
    }
}

// Close dropdowns when clicking outside
function handleClickOutside(e) {
    if (!e.target.closest('.relative') && !e.target.closest('.sticky')) {
        if (activeItem.value) {
            activeItem.value.showDropdown = false
            activeItem.value = null
        }
        previewGame.value = null
    }
}

onMounted(() => {
    loadUnmatched()
    document.addEventListener('click', handleClickOutside)
})
</script>
