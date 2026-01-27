<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 transition-colors duration-300">
        <!-- Header -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-lg border-b border-gray-200 shadow-sm dark:bg-slate-900/95 dark:border-slate-700/50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <router-link to="/" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent">
                            My Games
                        </h1>
                    </router-link>

                    <router-link
                        to="/"
                        class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                    >
                        Browse Games
                    </router-link>
                </div>
            </div>
        </header>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Status Tabs -->
            <div class="flex flex-wrap gap-2 mb-6">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    @click="currentStatus = tab.value"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        currentStatus === tab.value
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700'
                    ]"
                >
                    {{ tab.label }}
                    <span
                        v-if="statusCounts[tab.value] !== undefined"
                        :class="[
                            'ml-1.5 px-1.5 py-0.5 text-xs rounded',
                            currentStatus === tab.value
                                ? 'bg-indigo-500 text-white'
                                : 'bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-gray-400'
                        ]"
                    >
                        {{ statusCounts[tab.value] }}
                    </span>
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="space-y-4">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="bg-white dark:bg-slate-800 rounded-xl shadow-sm animate-pulse flex gap-4 p-4"
                >
                    <div class="w-20 h-28 shrink-0 bg-gray-200 dark:bg-slate-700 rounded-lg"></div>
                    <div class="flex-1 space-y-3">
                        <div class="h-5 bg-gray-200 dark:bg-slate-700 rounded w-3/4"></div>
                        <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded w-1/2"></div>
                        <div class="h-8 bg-gray-200 dark:bg-slate-700 rounded w-40"></div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else-if="filteredGames.length === 0"
                class="text-center py-16"
            >
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No games yet</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    {{ currentStatus === 'all' ? 'Start adding games to your list!' : `No games with status "${currentStatusLabel}"` }}
                </p>
                <router-link
                    to="/"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Browse Games
                </router-link>
            </div>

            <!-- Game List -->
            <div v-else class="space-y-4">
                <div
                    v-for="game in filteredGames"
                    :key="game.id"
                    class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 flex gap-4"
                >
                    <!-- Cover -->
                    <div class="w-20 h-28 shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-600 rounded-lg overflow-hidden">
                        <img
                            v-if="game.cover_url"
                            :src="game.cover_url"
                            :alt="game.title"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        />
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900 dark:text-white text-base mb-1 line-clamp-1">
                            {{ game.title }}
                        </h3>

                        <!-- Stats -->
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400 mb-3">
                            <span v-if="game.difficulty">Difficulty: {{ game.difficulty }}/10</span>
                            <span v-if="game.time_min || game.time_max">
                                Time: {{ game.time_min && game.time_max ? `${game.time_min}-${game.time_max}h` : game.time_min ? `${game.time_min}h+` : `~${game.time_max}h` }}
                            </span>
                        </div>

                        <!-- Status Selector -->
                        <div class="flex flex-wrap items-center gap-2">
                            <select
                                :value="game.status"
                                @change="updateGameStatus(game.id, $event.target.value)"
                                class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="want_to_play">Want to Play</option>
                                <option value="playing">Playing</option>
                                <option value="completed">Completed</option>
                                <option value="platinum">Platinum</option>
                                <option value="abandoned">Abandoned</option>
                            </select>

                            <!-- Guide Links -->
                            <div v-if="hasGuide(game)" class="flex items-center gap-1">
                                <a
                                    v-if="game.psnprofiles_url"
                                    :href="game.psnprofiles_url"
                                    target="_blank"
                                    class="px-2 py-1 rounded bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 text-xs font-bold hover:bg-blue-200 dark:hover:bg-blue-900/70 transition-colors"
                                >PSNP</a>
                                <a
                                    v-if="game.playstationtrophies_url"
                                    :href="game.playstationtrophies_url"
                                    target="_blank"
                                    class="px-2 py-1 rounded bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 text-xs font-bold hover:bg-purple-200 dark:hover:bg-purple-900/70 transition-colors"
                                >PST</a>
                                <a
                                    v-if="game.powerpyx_url"
                                    :href="game.powerpyx_url"
                                    target="_blank"
                                    class="px-2 py-1 rounded bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400 text-xs font-bold hover:bg-orange-200 dark:hover:bg-orange-900/70 transition-colors"
                                >PPX</a>
                            </div>

                            <div class="flex-1"></div>

                            <!-- Remove Button -->
                            <button
                                @click="removeGame(game.id)"
                                class="p-2 text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                title="Remove from list"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useHead } from '@vueuse/head'
import { useUserGames } from '../composables/useUserGames'

// SEO - noindex for private page
useHead({
    title: 'My Games | MyNextPlat',
    meta: [
        { name: 'robots', content: 'noindex, nofollow' },
    ],
})

const { games, loading, getMyGames, updateStatus, removeFromList } = useUserGames()

const currentStatus = ref('all')

const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'want_to_play', label: 'Want to Play' },
    { value: 'playing', label: 'Playing' },
    { value: 'completed', label: 'Completed' },
    { value: 'platinum', label: 'Platinum' },
    { value: 'abandoned', label: 'Abandoned' },
]

const currentStatusLabel = computed(() => {
    const tab = statusTabs.find(t => t.value === currentStatus.value)
    return tab ? tab.label : ''
})

const filteredGames = computed(() => {
    if (currentStatus.value === 'all') {
        return games.value
    }
    return games.value.filter(g => g.status === currentStatus.value)
})

const statusCounts = computed(() => {
    const counts = { all: games.value.length }
    for (const game of games.value) {
        counts[game.status] = (counts[game.status] || 0) + 1
    }
    return counts
})

function hasGuide(game) {
    return game.psnprofiles_url || game.playstationtrophies_url || game.powerpyx_url
}

async function updateGameStatus(gameId, status) {
    try {
        await updateStatus(gameId, status)
    } catch (e) {
        console.error('Failed to update status:', e)
    }
}

async function removeGame(gameId) {
    if (!confirm('Remove this game from your list?')) return

    try {
        await removeFromList(gameId)
    } catch (e) {
        console.error('Failed to remove game:', e)
    }
}

onMounted(() => {
    getMyGames()
})
</script>
