<template>
    <AppLayout title="My Games">
        <template #nav-tabs>
            <!-- Desktop Nav Tabs -->
            <div class="hidden sm:flex items-center gap-2">
                <div class="flex bg-gray-100 dark:bg-slate-800 rounded-lg p-1">
                    <router-link
                        to="/?view=all"
                        class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-700/50"
                    >
                        All Games
                    </router-link>
                    <router-link
                        v-if="isPsnLoaded"
                        to="/?view=psn"
                        class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-700/50"
                    >
                        PSN: {{ psnUser?.username }}
                    </router-link>
                    <span class="px-3 py-1.5 rounded-md text-sm font-medium bg-primary-600 text-white shadow-sm">
                        My Games
                    </span>
                </div>
                <router-link to="/?view=psn" class="px-2 py-1 text-xs font-bold text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors" :title="isPsnLoaded ? 'Load different PSN' : 'Load PSN Library'">
                    PSN
                </router-link>
            </div>
            <!-- Mobile Nav Tabs -->
            <div class="sm:hidden flex bg-gray-100 dark:bg-slate-800 rounded-lg p-0.5">
                <router-link to="/?view=all" class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors text-gray-600 dark:text-gray-400">
                    All Games
                </router-link>
                <router-link v-if="isPsnLoaded" to="/?view=psn" class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors text-gray-600 dark:text-gray-400">
                    PSN
                </router-link>
                <span class="px-2.5 py-1 rounded-md text-xs font-medium bg-primary-600 text-white shadow-sm">
                    My Games
                </span>
            </div>
        </template>

        <template #header-mobile>
            <router-link to="/?view=psn" class="px-2 py-1 text-xs font-bold rounded-lg transition-colors text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-slate-800" :title="isPsnLoaded ? 'Load different PSN' : 'Load PSN Library'">
                PSN
            </router-link>
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
                    <!-- Status Tabs - Horizontally Scrollable on Mobile -->
                    <div class="filter-row relative mb-3 -mx-4 px-4 sm:mx-0 sm:px-0 py-2 bg-gray-50 dark:bg-slate-950 z-10">
                        <div class="flex items-center gap-2">
                            <!-- Scrollable Status Pills -->
                            <div class="flex-1 overflow-x-auto scrollbar-hide -mx-4 px-4 sm:mx-0 sm:px-0">
                                <div class="flex gap-2 pb-1">
                                    <button
                                        v-for="tab in statusTabs"
                                        :key="tab.value"
                                        @click="switchStatus(tab.value)"
                                        :class="[
                                            'flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-all',
                                            currentStatus === tab.value
                                                ? 'bg-primary-600 text-white shadow-sm'
                                                : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 ring-1 ring-gray-200 dark:ring-slate-700'
                                        ]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', statusDotColors[tab.value]]"></span>
                                        {{ tab.label }}
                                        <span
                                            v-if="statusCounts[tab.value] !== undefined && statusCounts[tab.value] > 0"
                                            :class="[
                                                'px-1.5 py-0.5 text-[10px] font-bold rounded-full',
                                                currentStatus === tab.value
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400'
                                            ]"
                                        >
                                            {{ statusCounts[tab.value] }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Button (Mobile) -->
                            <button
                                @click="showMobileFilters = true"
                                :class="[
                                    'lg:hidden relative flex items-center justify-center p-2 rounded-full transition-all ring-1',
                                    activeFilterCount > 0
                                        ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 ring-primary-200 dark:ring-primary-800'
                                        : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 ring-gray-200 dark:ring-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                <span
                                    v-if="activeFilterCount > 0"
                                    class="absolute -top-1 -right-1 min-w-[18px] h-[18px] flex items-center justify-center text-[10px] font-bold rounded-full bg-primary-600 text-white"
                                >
                                    {{ activeFilterCount }}
                                </span>
                            </button>

                        </div>
                    </div>

                    <!-- Active Filters Summary (Mobile) -->
                    <div v-if="activeFilterCount > 0" class="lg:hidden flex flex-wrap gap-1.5 mb-3">
                        <span
                            v-for="filter in activeFiltersList"
                            :key="filter.key"
                            class="inline-flex items-center gap-1 px-2 py-1 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs rounded-full"
                        >
                            {{ filter.label }}
                            <button
                                @click="clearFilter(filter.key)"
                                class="p-0.5 hover:bg-primary-200 dark:hover:bg-primary-800 rounded-full transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </span>
                        <button
                            @click="clearAllFilters"
                            class="px-2 py-1 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                        >
                            Clear all
                        </button>
                    </div>

                    <!-- Sort Bar -->
                    <div class="flex items-center justify-between mb-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm p-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ total }}</span> games
                        </span>
                        <div class="flex items-center gap-2">
                            <!-- Share Button -->
                            <button
                                v-if="profilePublic && profileUrl"
                                @click="copyShareLink"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg transition-colors"
                                :class="[
                                    showShareCopied
                                        ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'
                                        : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700'
                                ]"
                                :title="showShareCopied ? 'Link copied!' : 'Copy share link'"
                            >
                                <svg v-if="showShareCopied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                <span class="hidden sm:inline">{{ showShareCopied ? 'Copied!' : 'Share' }}</span>
                            </button>
                            <router-link
                                v-else
                                to="/settings"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                title="Enable sharing in Settings"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                <span class="hidden sm:inline">Share</span>
                            </router-link>

                            <!-- Notifications Toggle -->
                            <button
                                @click="toggleNotifications"
                                :disabled="updatingNotifications"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg transition-colors"
                                :class="[
                                    notifyNewGuides
                                        ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'
                                        : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700'
                                ]"
                                :title="notifyNewGuides ? 'Email notifications enabled' : 'Email notifications disabled'"
                            >
                                <svg v-if="updatingNotifications" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <span class="hidden sm:inline">Notify</span>
                            </button>

                            <!-- Separator -->
                            <div class="hidden sm:block w-px h-5 bg-gray-200 dark:bg-slate-600"></div>

                            <label class="hidden sm:block text-sm text-gray-500 dark:text-gray-400">Sort:</label>
                            <select
                                v-model="sortBy"
                                @change="loadGames"
                                class="border-0 bg-gray-100 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="title">Title</option>
                                <option value="release_date">Release Date</option>
                                <option value="added_at">Date Added</option>
                                <option value="difficulty">Difficulty</option>
                                <option value="time_min">Completion Time</option>
                                <option value="user_score">User Score</option>
                                <option value="critic_score">Critic Score</option>
                                <option value="playthroughs_required">Playthroughs</option>
                                <option value="missable_trophies">Missables</option>
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

        <!-- Mobile Filters Bottom Sheet -->
        <Teleport to="body">
            <Transition name="bottomsheet">
                <div
                    v-if="showMobileFilters"
                    class="fixed inset-0 z-50 lg:hidden"
                >
                    <!-- Backdrop -->
                    <div
                        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                        @click="showMobileFilters = false"
                    ></div>

                    <!-- Bottom Sheet -->
                    <div class="absolute inset-x-0 bottom-0 max-h-[85vh] bg-white dark:bg-slate-900 rounded-t-2xl shadow-xl flex flex-col">
                        <!-- Handle -->
                        <div class="flex justify-center pt-3 pb-2">
                            <div class="w-10 h-1 bg-gray-300 dark:bg-slate-600 rounded-full"></div>
                        </div>

                        <!-- Header -->
                        <div class="flex items-center justify-between px-4 pb-3 border-b border-gray-100 dark:border-slate-800">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Filters</h2>
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="activeFilterCount > 0"
                                    @click="clearAllFilters"
                                    class="px-3 py-1 text-sm text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-lg transition-colors"
                                >
                                    Reset
                                </button>
                                <button
                                    @click="showMobileFilters = false"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Scrollable Content -->
                        <div class="flex-1 overflow-y-auto overscroll-contain p-4">
                            <GameFilters @update:filters="onFilterChange" />
                        </div>

                        <!-- Footer -->
                        <div class="shrink-0 p-4 border-t border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-slate-800/50">
                            <button
                                @click="showMobileFilters = false"
                                class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition-colors shadow-sm"
                            >
                                Show {{ total.toLocaleString() }} Results
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import { useAuth } from '../composables/useAuth'
import { useAppConfig } from '../composables/useAppConfig'
import { useUserGames } from '../composables/useUserGames'
import { usePSNLibrary } from '../composables/usePSNLibrary'
import AppLayout from '../components/AppLayout.vue'
import GameFilters from '../components/GameFilters.vue'
import GameCard from '../components/GameCard.vue'

const { appName } = useAppConfig()

// SEO - noindex for private page
useHead({
    title: `My Games | ${appName}`,
    meta: [
        { name: 'robots', content: 'noindex, nofollow' },
    ],
})

const route = useRoute()
const { notifyNewGuides, profilePublic, profileUrl, updatePreferences } = useAuth()
const { updateStatus } = useUserGames()
const { isPsnLoaded, psnUser } = usePSNLibrary()

const showShareCopied = ref(false)

const games = ref([])
const loading = ref(true)
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const statusCounts = ref({ all: 0 })
const currentStatus = ref(sessionStorage.getItem('myGamesStatus') || 'all')
const sortBy = ref(sessionStorage.getItem('sortBy') || 'critic_score')
const sortOrder = ref(sessionStorage.getItem('sortOrder') || 'desc')
const showMobileFilters = ref(false)
const updatingNotifications = ref(false)

// Load saved filters from sessionStorage
const savedMyGamesFilters = (() => {
    try {
        const saved = sessionStorage.getItem('gameFilters')
        return saved ? JSON.parse(saved) : {}
    } catch {
        return {}
    }
})()
const filters = reactive({ ...savedMyGamesFilters })

const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'backlog', label: 'Backlog' },
    { value: 'in_progress', label: 'Playing' },
    { value: 'completed', label: '100%' },
    { value: 'platinumed', label: 'Platinum' },
    { value: 'abandoned', label: 'Dropped' },
]

const statusDotColors = {
    all: 'bg-gray-400',
    backlog: 'bg-slate-400',
    in_progress: 'bg-sky-500',
    completed: 'bg-emerald-500',
    platinumed: 'bg-amber-500',
    abandoned: 'bg-rose-500',
}

const hasMore = computed(() => currentPage.value < lastPage.value)

const hasActiveFilters = computed(() => {
    return filters.search ||
           filters.platform_ids?.length ||
           filters.genre_ids?.length ||
           filters.difficulty_min > 1 ||
           filters.difficulty_max < 10 ||
           currentStatus.value !== 'all'
})

// Count of active filters (excluding status which has its own UI)
const activeFilterCount = computed(() => {
    let count = 0
    if (filters.search) count++
    if (filters.platform_ids?.length) count++
    if (filters.genre_ids?.length) count++
    if (filters.tag_ids?.length) count++
    if (filters.difficulty_min > 1 || filters.difficulty_max < 10) count++
    if (filters.time_min > 0 || filters.time_max < 200) count++
    if (filters.max_playthroughs) count++
    if (filters.user_score_min > 0 || filters.user_score_max < 100) count++
    if (filters.critic_score_min > 0 || filters.critic_score_max < 100) count++
    if (filters.has_guide === true || filters.has_guide === false) count++
    if (filters.has_online_trophies === false) count++
    if (filters.missable_trophies === false) count++
    if (filters.exclude_unobtainable === true) count++
    return count
})

// List of active filters for display
const activeFiltersList = computed(() => {
    const list = []
    if (filters.search) list.push({ key: 'search', label: `"${filters.search}"` })
    if (filters.platform_ids?.length) list.push({ key: 'platform_ids', label: `${filters.platform_ids.length} platform${filters.platform_ids.length > 1 ? 's' : ''}` })
    if (filters.genre_ids?.length) list.push({ key: 'genre_ids', label: `${filters.genre_ids.length} genre${filters.genre_ids.length > 1 ? 's' : ''}` })
    if (filters.difficulty_min > 1 || filters.difficulty_max < 10) list.push({ key: 'difficulty', label: `Diff: ${filters.difficulty_min}-${filters.difficulty_max}` })
    if (filters.time_min > 0 || filters.time_max < 200) list.push({ key: 'time', label: `Time: ${filters.time_min}-${filters.time_max}h` })
    if (filters.has_guide === true) list.push({ key: 'has_guide', label: 'Has guide' })
    if (filters.has_guide === false) list.push({ key: 'has_guide', label: 'No guide' })
    if (filters.has_online_trophies === false) list.push({ key: 'has_online_trophies', label: 'No online' })
    if (filters.missable_trophies === false) list.push({ key: 'missable_trophies', label: 'No missables' })
    if (filters.exclude_unobtainable === true) list.push({ key: 'exclude_unobtainable', label: 'No unobtainable' })
    return list
})

function clearFilter(key) {
    if (key === 'search') filters.search = ''
    else if (key === 'platform_ids') filters.platform_ids = []
    else if (key === 'genre_ids') filters.genre_ids = []
    else if (key === 'difficulty') { filters.difficulty_min = 1; filters.difficulty_max = 10 }
    else if (key === 'time') { filters.time_min = 0; filters.time_max = 200 }
    else if (key === 'has_guide') filters.has_guide = null
    else if (key === 'has_online_trophies') filters.has_online_trophies = null
    else if (key === 'missable_trophies') filters.missable_trophies = null
    else if (key === 'exclude_unobtainable') filters.exclude_unobtainable = null

    currentPage.value = 1
    games.value = []
    loadGames()
}

function clearAllFilters() {
    Object.keys(filters).forEach(key => {
        if (Array.isArray(filters[key])) filters[key] = []
        else filters[key] = null
    })
    filters.difficulty_min = 1
    filters.difficulty_max = 10
    filters.time_min = 0
    filters.time_max = 200
    filters.user_score_min = 0
    filters.user_score_max = 100
    filters.critic_score_min = 0
    filters.critic_score_max = 100
    // Clear saved filters from sessionStorage
    sessionStorage.removeItem('gameFilters')
    currentPage.value = 1
    games.value = []
    loadGames()
}

function onFilterChange(newFilters) {
    Object.assign(filters, newFilters)
    // Save filters to sessionStorage
    sessionStorage.setItem('gameFilters', JSON.stringify(newFilters))
    currentPage.value = 1
    games.value = []
    loadGames()
}

function switchStatus(status) {
    currentStatus.value = status
    sessionStorage.setItem('myGamesStatus', status)
    currentPage.value = 1
    games.value = []
    loadGames()
}

function toggleSortOrder() {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    sessionStorage.setItem('sortOrder', sortOrder.value)
    currentPage.value = 1
    games.value = []
    loadGames()
}

// Watch sortBy changes to save to sessionStorage
watch(sortBy, (newVal) => {
    sessionStorage.setItem('sortBy', newVal)
    currentPage.value = 1
    games.value = []
    loadGames()
})

let loadGamesController = null

async function loadGames() {
    // Cancel any in-flight request
    if (loadGamesController) {
        loadGamesController.abort()
    }
    loadGamesController = new AbortController()

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
        if (filters.user_score_min > 0) params.append('user_score_min', filters.user_score_min)
        if (filters.user_score_max < 100) params.append('user_score_max', filters.user_score_max)
        if (filters.critic_score_min > 0) params.append('critic_score_min', filters.critic_score_min)
        if (filters.critic_score_max < 100) params.append('critic_score_max', filters.critic_score_max)
        if (filters.has_online_trophies === false) params.append('has_online_trophies', 'false')
        if (filters.missable_trophies === false) params.append('missable_trophies', 'false')
        if (filters.exclude_unobtainable === true) params.append('exclude_unobtainable', 'true')
        if (filters.has_platinum === true) params.append('has_platinum', 'true')

        // Sorting
        params.append('sort_by', sortBy.value)
        params.append('sort_order', sortOrder.value)

        // Pagination
        params.append('page', currentPage.value)
        params.append('per_page', 24)

        const response = await fetch(`/api/my-games?${params}`, {
            credentials: 'include',
            signal: loadGamesController.signal,
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
        if (e.name === 'AbortError') return
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
    // Find the game first
    const game = games.value.find(g => g.id === gameId)
    if (!game) return

    const previousStatus = game.user_status

    // Optimistically update local state immediately
    game.user_status = status

    // Update status counts locally
    if (statusCounts.value[previousStatus]) {
        statusCounts.value[previousStatus]--
    }
    if (statusCounts.value[status]) {
        statusCounts.value[status]++
    } else {
        statusCounts.value[status] = 1
    }

    // If we're filtering by a specific status and the game no longer matches, remove it from view
    if (currentStatus.value !== 'all' && status !== currentStatus.value) {
        games.value = games.value.filter(g => g.id !== gameId)
        total.value--
    }

    try {
        await updateStatus(gameId, status)
    } catch (e) {
        // Revert on error
        game.user_status = previousStatus
        if (statusCounts.value[previousStatus]) {
            statusCounts.value[previousStatus]++
        }
        if (statusCounts.value[status]) {
            statusCounts.value[status]--
        }
        // Re-add to list if we removed it
        if (currentStatus.value !== 'all' && status !== currentStatus.value) {
            // Reload to get correct state
            currentPage.value = 1
            games.value = []
            loadGames()
        }
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

async function copyShareLink() {
    if (!profileUrl.value) return

    try {
        await navigator.clipboard.writeText(profileUrl.value)
        showShareCopied.value = true
        setTimeout(() => {
            showShareCopied.value = false
        }, 2000)
    } catch (e) {
        console.error('Failed to copy:', e)
    }
}

onMounted(() => {
    loadGames()

    // Handle unsubscribe from email link
    if (route.query.notifications === 'off') {
        updatePreferences({ notify_new_guides: false })
    }
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

/* Bottom Sheet Animation */
.bottomsheet-enter-active,
.bottomsheet-leave-active {
    transition: opacity 0.3s ease;
}
.bottomsheet-enter-active > div:last-child,
.bottomsheet-leave-active > div:last-child {
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.bottomsheet-enter-from,
.bottomsheet-leave-to {
    opacity: 0;
}
.bottomsheet-enter-from > div:last-child,
.bottomsheet-leave-to > div:last-child {
    transform: translateY(100%);
}

/* Hide scrollbar for horizontal scroll */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Sticky filter row - only on taller screens */
@media (min-height: 600px) {
    .filter-row {
        position: sticky;
        top: 64px; /* Header height */
    }
}
</style>
