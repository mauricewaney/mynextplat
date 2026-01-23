<template>
    <AdminLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Unmatched Trophy URLs</h1>
                    <p class="text-gray-600 mt-1">Manually match trophy guide URLs to games</p>
                </div>
                <div class="flex items-center gap-4">
                    <select v-model="sourceFilter" class="border-gray-300 rounded-md shadow-sm">
                        <option value="">All Sources</option>
                        <option value="psnprofiles">PSNProfiles</option>
                        <option value="playstationtrophies">PlayStationTrophies</option>
                    </select>
                    <div class="text-sm text-gray-500">
                        {{ unmatched.length }} unmatched
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-red-600">{{ stats.psnprofiles_unmatched || 0 }}</div>
                    <div class="text-sm text-gray-500">PSNProfiles Unmatched</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-green-600">{{ stats.psnprofiles_matched || 0 }}</div>
                    <div class="text-sm text-gray-500">PSNProfiles Matched</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-red-600">{{ stats.pst_unmatched || 0 }}</div>
                    <div class="text-sm text-gray-500">PST Unmatched</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-green-600">{{ stats.pst_matched || 0 }}</div>
                    <div class="text-sm text-gray-500">PST Matched</div>
                </div>
            </div>

            <!-- Search -->
            <div class="bg-white rounded-lg shadow p-4">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search unmatched URLs by title..."
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
            </div>

            <!-- List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div v-if="loading" class="p-8 text-center text-gray-500">Loading...</div>
                <div v-else-if="filtered.length === 0" class="p-8 text-center text-gray-500">
                    No unmatched URLs found
                </div>
                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="item in paginated"
                        :key="item.id"
                        class="p-4 hover:bg-gray-50"
                    >
                        <div class="flex items-start gap-4">
                            <!-- URL Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span
                                        :class="item.source === 'psnprofiles' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'"
                                        class="px-2 py-0.5 rounded text-xs font-medium"
                                    >
                                        {{ item.source === 'psnprofiles' ? 'PSNProfiles' : 'PST' }}
                                    </span>
                                    <span class="font-medium text-gray-900">{{ item.extracted_title }}</span>
                                </div>
                                <a
                                    :href="item.url"
                                    target="_blank"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 truncate block"
                                >
                                    {{ item.url }}
                                </a>
                                <div class="text-xs text-gray-400 mt-1">
                                    Slug: {{ item.extracted_slug }}
                                </div>
                            </div>

                            <!-- Game Selector -->
                            <div class="w-80 shrink-0">
                                <div class="relative">
                                    <input
                                        v-model="item.gameSearch"
                                        type="text"
                                        placeholder="Search for game..."
                                        class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        @focus="item.showDropdown = true"
                                        @input="searchGames(item)"
                                    />
                                    <!-- Dropdown -->
                                    <div
                                        v-if="item.showDropdown && item.gameResults?.length > 0"
                                        class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto"
                                    >
                                        <button
                                            v-for="game in item.gameResults"
                                            :key="game.id"
                                            @click="matchToGame(item, game)"
                                            class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 flex items-center gap-2"
                                        >
                                            <img
                                                v-if="game.cover_url"
                                                :src="game.cover_url"
                                                class="w-8 h-8 object-cover rounded"
                                            />
                                            <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center text-gray-400" v-else>
                                                ?
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium truncate">{{ game.title }}</div>
                                                <div class="text-xs text-gray-400">{{ game.slug }}</div>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    @click="skipUrl(item)"
                                    class="p-2 text-gray-400 hover:text-red-600"
                                    title="Delete URL"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="px-4 py-3 bg-gray-50 border-t flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Showing {{ (page - 1) * perPage + 1 }} - {{ Math.min(page * perPage, filtered.length) }} of {{ filtered.length }}
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="page = Math.max(1, page - 1)"
                            :disabled="page === 1"
                            class="px-3 py-1 border rounded text-sm disabled:opacity-50"
                        >
                            Prev
                        </button>
                        <span class="text-sm">Page {{ page }} of {{ totalPages }}</span>
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

            <!-- Back Link -->
            <div class="flex gap-4">
                <router-link to="/admin/games" class="text-indigo-600 hover:text-indigo-800">
                    ← Back to Games
                </router-link>
                <router-link to="/admin/trophy-import" class="text-indigo-600 hover:text-indigo-800">
                    → Import More URLs
                </router-link>
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
const perPage = 20
const stats = ref({})

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

async function loadUnmatched() {
    loading.value = true
    try {
        const response = await fetch('/api/admin/trophy-urls/unmatched')
        const data = await response.json()
        unmatched.value = data.urls.map(u => ({
            ...u,
            gameSearch: '',
            gameResults: [],
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
            const response = await fetch(`/api/admin/games?search=${encodeURIComponent(item.gameSearch)}&per_page=10`)
            const data = await response.json()
            item.gameResults = data.data || []
            item.showDropdown = true
        } catch (e) {
            console.error('Failed to search games:', e)
        }
    }, 300)
}

async function matchToGame(item, game) {
    try {
        const response = await fetch(`/api/admin/trophy-urls/${item.id}/match`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ game_id: game.id }),
        })

        if (response.ok) {
            // Remove from list
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            // Update stats
            if (item.source === 'psnprofiles') {
                stats.value.psnprofiles_unmatched--
                stats.value.psnprofiles_matched++
            } else {
                stats.value.pst_unmatched--
                stats.value.pst_matched++
            }
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
    if (!e.target.closest('.relative')) {
        unmatched.value.forEach(u => u.showDropdown = false)
    }
}

onMounted(() => {
    loadUnmatched()
    document.addEventListener('click', handleClickOutside)
})
</script>
