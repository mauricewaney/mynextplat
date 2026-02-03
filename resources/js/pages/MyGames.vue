<template>
    <AppLayout title="My Games">
        <!-- Navigation Tabs Header -->
        <template #header-left>
            <!-- View Mode Tabs - Desktop -->
            <div class="hidden sm:flex bg-gray-100 dark:bg-slate-800 rounded-lg p-1">
                <router-link
                    to="/"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                >
                    All Games
                </router-link>
                <router-link
                    to="/?view=psn"
                    :class="[
                        'px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5',
                        isPsnLoaded
                            ? 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                            : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400'
                    ]"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5m-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6m6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5m-6 8h1.5v1.5H13V13m1.5 1.5H16V16h-1.5v-1.5M16 13h1.5v1.5H16V13m-3 3h1.5v1.5H13V16m1.5 1.5H16V19h-1.5v-1.5M16 16h1.5v1.5H16V16m1.5-1.5H19V16h-1.5v-1.5m0 3H19V19h-1.5v-1.5M19 13h-1.5v1.5H19V13"/>
                    </svg>
                    <span v-if="isPsnLoaded">PSN: {{ psnUser?.username }}</span>
                    <span v-else>Load PSN...</span>
                </router-link>
                <span
                    class="px-3 py-1.5 rounded-md text-sm font-medium bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm"
                >
                    My Games
                </span>
            </div>

            <!-- View Mode Dropdown - Mobile -->
            <div class="sm:hidden relative view-mode-menu-container">
                <button
                    @click="showViewModeMenu = !showViewModeMenu"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-100 dark:bg-slate-800 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    <span>My Games</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <!-- Dropdown Menu -->
                <div
                    v-if="showViewModeMenu"
                    class="absolute top-full left-0 mt-1 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-gray-200 dark:border-slate-700 py-1 min-w-[140px] z-50"
                >
                    <router-link
                        to="/"
                        class="block w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700"
                        @click="showViewModeMenu = false"
                    >
                        All Games
                    </router-link>
                    <router-link
                        to="/?view=psn"
                        :class="[
                            'block w-full px-3 py-2 text-left text-sm flex items-center gap-2',
                            isPsnLoaded
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700'
                                : 'text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-slate-700'
                        ]"
                        @click="showViewModeMenu = false"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5m-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6m6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5m-6 8h1.5v1.5H13V13m1.5 1.5H16V16h-1.5v-1.5M16 13h1.5v1.5H16V13m-3 3h1.5v1.5H13V16m1.5 1.5H16V19h-1.5v-1.5M16 16h1.5v1.5H16V16m1.5-1.5H19V16h-1.5v-1.5m0 3H19V19h-1.5v-1.5M19 13h-1.5v1.5H19V13"/>
                        </svg>
                        <span v-if="isPsnLoaded">PSN: {{ psnUser?.username }}</span>
                        <span v-else>Load PSN...</span>
                    </router-link>
                    <span
                        class="block w-full px-3 py-2 text-left text-sm bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400"
                    >
                        My Games
                    </span>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex gap-8">
                <!-- Sidebar Filters (Desktop) -->
                <aside class="hidden lg:block w-[420px] shrink-0">
                    <div class="sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto pr-2 scrollbar-thin">
                        <GameFilters @update:filters="onFilterChange" />
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 min-w-0">
                    <!-- Status Tabs & Settings -->
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tab in statusTabs"
                                :key="tab.value"
                                @click="switchStatus(tab.value)"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                    currentStatus === tab.value
                                        ? 'bg-primary-600 text-white'
                                        : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700'
                                ]"
                            >
                                {{ tab.label }}
                                <span
                                    v-if="statusCounts[tab.value] !== undefined"
                                    :class="[
                                        'ml-1.5 px-1.5 py-0.5 text-xs rounded',
                                        currentStatus === tab.value
                                            ? 'bg-primary-500 text-white'
                                            : 'bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-gray-400'
                                    ]"
                                >
                                    {{ statusCounts[tab.value] }}
                                </span>
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Email Notifications Toggle -->
                            <button
                                @click="toggleNotifications"
                                :disabled="updatingNotifications"
                                class="flex items-center gap-2 px-3 py-1.5 text-sm rounded-lg transition-colors"
                                :class="[
                                    notifyNewGuides
                                        ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50'
                                        : 'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700'
                                ]"
                                :title="notifyNewGuides ? 'Email notifications enabled' : 'Email notifications disabled'"
                            >
                                <svg v-if="updatingNotifications" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else-if="notifyNewGuides" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <span class="hidden sm:inline">{{ notifyNewGuides ? 'Notify' : 'Notify' }}</span>
                            </button>

                            <!-- Mobile Filter Button -->
                            <button
                                @click="showMobileFilters = true"
                                class="lg:hidden p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Sort Bar -->
                    <div class="flex items-center justify-between mb-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm p-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ total }}</span> games
                        </span>
                        <div class="flex items-center gap-2">
                            <label class="hidden sm:block text-sm text-gray-500 dark:text-gray-400">Sort:</label>
                            <select
                                v-model="sortBy"
                                @change="loadGames"
                                class="border-0 bg-gray-100 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="added_at">Date Added</option>
                                <option value="title">Title</option>
                                <option value="difficulty">Difficulty</option>
                                <option value="time_min">Completion Time</option>
                                <option value="critic_score">Critic Score</option>
                            </select>
                            <button
                                @click="toggleSortOrder"
                                class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                :title="sortOrder === 'asc' ? 'Ascending' : 'Descending'"
                            >
                                <svg
                                    :class="['w-5 h-5 transition-transform', sortOrder === 'desc' ? 'rotate-180' : '']"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading && games.length === 0" class="space-y-4">
                        <div
                            v-for="n in 6"
                            :key="n"
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm animate-pulse flex gap-4 p-3 sm:p-4"
                        >
                            <div class="w-24 sm:w-28 h-32 sm:h-36 shrink-0 bg-gray-200 dark:bg-slate-700 rounded-lg"></div>
                            <div class="flex-1 space-y-3">
                                <div class="h-5 bg-gray-200 dark:bg-slate-700 rounded w-3/4"></div>
                                <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded w-1/2"></div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-else-if="!loading && games.length === 0"
                        class="text-center py-16"
                    >
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No games found</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            {{ hasActiveFilters ? 'Try adjusting your filters' : 'Start adding games to your list!' }}
                        </p>
                        <router-link
                            v-if="!hasActiveFilters"
                            to="/"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Browse Games
                        </router-link>
                    </div>

                    <!-- Game List -->
                    <div v-else class="space-y-4">
                        <GameCard
                            v-for="game in games"
                            :key="game.id"
                            :game="game"
                            @update-status="updateGameStatus"
                            @removed="onGameRemoved"
                        />
                    </div>

                    <!-- Load More -->
                    <div v-if="hasMore && games.length > 0" class="mt-8 text-center">
                        <button
                            @click="loadMore"
                            :disabled="loading"
                            class="px-8 py-3 bg-white dark:bg-slate-800 text-primary-600 dark:text-primary-400 font-medium rounded-xl shadow-sm hover:shadow-md dark:shadow-slate-900/50 transition-all disabled:opacity-50"
                        >
                            <span v-if="loading" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading...
                            </span>
                            <span v-else>Load more games</span>
                        </button>
                    </div>

                    <!-- Showing count -->
                    <div v-if="games.length > 0" class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        Showing {{ games.length }} of {{ total }} games
                    </div>
                </main>
            </div>
        </div>

        <!-- Mobile Filters Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showMobileFilters"
                    class="fixed inset-0 z-50 lg:hidden"
                >
                    <div class="absolute inset-0 bg-black/50" @click="showMobileFilters = false"></div>
                    <div class="absolute inset-y-0 right-0 w-full max-w-sm bg-gray-50 dark:bg-slate-900 shadow-xl overflow-y-auto">
                        <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-100 dark:border-slate-700 px-4 py-3 flex items-center justify-between">
                            <h2 class="font-semibold text-gray-900 dark:text-white">Filters</h2>
                            <button
                                @click="showMobileFilters = false"
                                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="p-4 pb-24">
                            <GameFilters @update:filters="onFilterChange" />
                        </div>
                        <div class="sticky bottom-0 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700 p-4">
                            <button
                                @click="showMobileFilters = false"
                                class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
                            >
                                Show {{ total }} Results
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import { useAuth } from '../composables/useAuth'
import { useUserGames } from '../composables/useUserGames'
import { usePSNLibrary } from '../composables/usePSNLibrary'
import AppLayout from '../components/AppLayout.vue'
import GameFilters from '../components/GameFilters.vue'
import GameCard from '../components/GameCard.vue'

// SEO - noindex for private page
useHead({
    title: 'My Games | MyNextPlat',
    meta: [
        { name: 'robots', content: 'noindex, nofollow' },
    ],
})

const route = useRoute()
const { notifyNewGuides, updatePreferences } = useAuth()
const { updateStatus } = useUserGames()
const { isPsnLoaded, psnUser } = usePSNLibrary()

const games = ref([])
const loading = ref(true)
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const statusCounts = ref({ all: 0 })
const currentStatus = ref('all')
const sortBy = ref('added_at')
const sortOrder = ref('desc')
const showMobileFilters = ref(false)
const showViewModeMenu = ref(false)
const updatingNotifications = ref(false)
const filters = reactive({})

const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'backlog', label: 'Backlog' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'platinumed', label: 'Platinumed' },
]

const hasMore = computed(() => currentPage.value < lastPage.value)

const hasActiveFilters = computed(() => {
    return filters.search ||
           filters.platform_ids?.length ||
           filters.genre_ids?.length ||
           filters.difficulty_min > 1 ||
           filters.difficulty_max < 10 ||
           currentStatus.value !== 'all'
})

function onFilterChange(newFilters) {
    Object.assign(filters, newFilters)
    currentPage.value = 1
    games.value = []
    loadGames()
}

function switchStatus(status) {
    currentStatus.value = status
    currentPage.value = 1
    games.value = []
    loadGames()
}

function toggleSortOrder() {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    currentPage.value = 1
    games.value = []
    loadGames()
}

async function loadGames() {
    loading.value = true

    try {
        const params = new URLSearchParams()

        // Status filter
        if (currentStatus.value !== 'all') {
            params.append('status', currentStatus.value)
        }

        // Apply game filters
        if (filters.search) params.append('search', filters.search)
        if (filters.has_guide === true) params.append('has_guide', 'true')
        if (filters.has_guide === false) params.append('has_guide', 'false')
        if (filters.platform_ids?.length) {
            filters.platform_ids.forEach(id => params.append('platform_ids[]', id))
        }
        if (filters.genre_ids?.length) {
            filters.genre_ids.forEach(id => params.append('genre_ids[]', id))
        }
        if (filters.tag_ids?.length) {
            filters.tag_ids.forEach(id => params.append('tag_ids[]', id))
        }
        if (filters.difficulty_min > 1) params.append('difficulty_min', filters.difficulty_min)
        if (filters.difficulty_max < 10) params.append('difficulty_max', filters.difficulty_max)
        if (filters.time_min > 0) params.append('time_min', filters.time_min)
        if (filters.time_max < 200) params.append('time_max', filters.time_max)
        if (filters.max_playthroughs) params.append('max_playthroughs', filters.max_playthroughs)
        if (filters.min_score > 0) params.append('min_score', filters.min_score)
        if (filters.has_online_trophies === false) params.append('has_online_trophies', 'false')
        if (filters.missable_trophies === false) params.append('missable_trophies', 'false')

        // Sorting
        params.append('sort_by', sortBy.value)
        params.append('sort_order', sortOrder.value)

        // Pagination
        params.append('page', currentPage.value)
        params.append('per_page', 24)

        const response = await fetch(`/api/my-games?${params}`, {
            credentials: 'include',
        })
        const data = await response.json()

        if (currentPage.value === 1) {
            games.value = data.data
        } else {
            games.value.push(...data.data)
        }

        total.value = data.total
        lastPage.value = data.last_page
        statusCounts.value = data.status_counts || { all: data.total }
    } catch (e) {
        console.error('Failed to load games:', e)
    } finally {
        loading.value = false
    }
}

function loadMore() {
    currentPage.value++
    loadGames()
}

async function updateGameStatus(gameId, status) {
    try {
        await updateStatus(gameId, status)
        // Update local state
        const game = games.value.find(g => g.id === gameId)
        if (game) {
            game.user_status = status
        }
        // Reload to update counts
        loadGames()
    } catch (e) {
        console.error('Failed to update status:', e)
    }
}

function onGameRemoved(gameId) {
    // Remove from local list and reload to update counts
    games.value = games.value.filter(g => g.id !== gameId)
    total.value--
    loadGames()
}

async function toggleNotifications() {
    if (updatingNotifications.value) return

    updatingNotifications.value = true
    try {
        await updatePreferences({ notify_new_guides: !notifyNewGuides.value })
    } catch (e) {
        console.error('Failed to update notifications:', e)
    } finally {
        updatingNotifications.value = false
    }
}

// Close dropdown when clicking outside
function handleClickOutside(e) {
    if (showViewModeMenu.value && !e.target.closest('.view-mode-menu-container')) {
        showViewModeMenu.value = false
    }
}

onMounted(() => {
    loadGames()
    document.addEventListener('click', handleClickOutside)

    // Handle unsubscribe from email link
    if (route.query.notifications === 'off') {
        updatePreferences({ notify_new_guides: false })
    }
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
