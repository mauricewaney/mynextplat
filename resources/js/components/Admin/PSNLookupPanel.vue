<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-hidden"
    >
        <!-- Backdrop -->
        <div
            class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"
            @click="$emit('close')"
        ></div>

        <!-- Panel -->
        <div class="absolute inset-y-0 right-0 max-w-4xl w-full">
            <div class="h-full bg-white dark:bg-slate-800 shadow-xl flex flex-col">
                <!-- Header -->
                <div class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center flex-shrink-0">
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5m-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6m6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5m-6 8h1.5v1.5H13V13m1.5 1.5H16V16h-1.5v-1.5M16 13h1.5v1.5H16V13m-3 3h1.5v1.5H13V16m1.5 1.5H16V19h-1.5v-1.5M16 16h1.5v1.5H16V16m1.5-1.5H19V16h-1.5v-1.5m0 3H19V19h-1.5v-1.5M19 13h-1.5v1.5H19V13"/>
                        </svg>
                        PSN Library Lookup
                    </h2>
                    <button @click="$emit('close')" class="text-white hover:text-blue-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-700 border-b dark:border-slate-600 flex-shrink-0">
                    <form @submit.prevent="lookupUser" class="flex gap-3">
                        <div class="flex-1">
                            <input
                                v-model="username"
                                type="text"
                                placeholder="Enter PSN username..."
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :disabled="loading"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="loading || !username.trim()"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>{{ loading ? 'Searching...' : 'Search' }}</span>
                        </button>
                    </form>

                    <!-- Filters -->
                    <div v-if="userData" class="mt-3 flex flex-wrap gap-2">
                        <button
                            @click="filter = 'all'"
                            :class="['px-3 py-1 rounded-full text-sm font-medium transition-colors', filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-slate-500']"
                        >
                            All ({{ userData.total_games }})
                        </button>
                        <button
                            @click="filter = 'unplatinumed'"
                            :class="['px-3 py-1 rounded-full text-sm font-medium transition-colors', filter === 'unplatinumed' ? 'bg-yellow-600 text-white' : 'bg-gray-200 dark:bg-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-slate-500']"
                        >
                            Unplatinumed ({{ userData.stats.platinums_remaining }})
                        </button>
                        <button
                            @click="filter = 'with_guide'"
                            :class="['px-3 py-1 rounded-full text-sm font-medium transition-colors', filter === 'with_guide' ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-slate-500']"
                        >
                            Has Guide ({{ userData.stats.with_guide }})
                        </button>
                        <button
                            @click="filter = 'no_match'"
                            :class="['px-3 py-1 rounded-full text-sm font-medium transition-colors', filter === 'no_match' ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-slate-500']"
                        >
                            No Match ({{ userData.total_games - userData.stats.matched_in_database }})
                        </button>
                    </div>
                </div>

                <!-- User Info -->
                <div v-if="userData" class="px-6 py-3 bg-gray-100 dark:bg-slate-700/50 border-b dark:border-slate-600 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <img
                            v-if="userData.user.avatar"
                            :src="userData.user.avatar"
                            :alt="userData.user.username"
                            class="w-10 h-10 rounded-full"
                        />
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ userData.user.username }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ userData.total_games }} games</div>
                        </div>
                    </div>
                    <div class="flex gap-4 text-sm">
                        <div class="text-center">
                            <div class="font-bold text-yellow-600">{{ userData.stats.platinums_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400">Platinums</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-blue-600">{{ userData.stats.matched_in_database }}</div>
                            <div class="text-gray-500 dark:text-gray-400">Matched</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-green-600">{{ userData.stats.with_guide }}</div>
                            <div class="text-gray-500 dark:text-gray-400">With Guide</div>
                        </div>
                    </div>
                </div>

                <!-- Error -->
                <div v-if="error" class="px-6 py-4 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 flex-shrink-0">
                    {{ error }}
                </div>

                <!-- Games List -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="loading" class="flex items-center justify-center h-64">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                            <p class="mt-4 text-gray-600 dark:text-gray-400">Fetching PSN library...</p>
                        </div>
                    </div>

                    <div v-else-if="!userData" class="flex items-center justify-center h-64 text-gray-500 dark:text-gray-400">
                        Enter a PSN username to search
                    </div>

                    <div v-else-if="filteredGames.length === 0" class="flex items-center justify-center h-64 text-gray-500 dark:text-gray-400">
                        No games match the current filter
                    </div>

                    <div v-else class="divide-y divide-gray-200 dark:divide-slate-700">
                        <div
                            v-for="(game, index) in filteredGames"
                            :key="index"
                            class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 flex items-start gap-4"
                        >
                            <!-- Game Image -->
                            <img
                                v-if="game.psn_image"
                                :src="game.psn_image"
                                :alt="game.psn_name"
                                class="w-16 h-16 object-cover rounded"
                            />
                            <div v-else class="w-16 h-16 bg-gray-200 dark:bg-slate-700 rounded flex items-center justify-center text-gray-400 dark:text-gray-500 text-xs">
                                No Image
                            </div>

                            <!-- Game Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ game.psn_name }}</h3>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 px-2 py-0.5 rounded">
                                                {{ game.psn_platform }}
                                            </span>
                                            <span
                                                v-if="game.has_platinum"
                                                :class="['text-xs px-2 py-0.5 rounded', game.earned_platinum ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400']"
                                            >
                                                {{ game.earned_platinum ? '🏆 Platinum' : 'Platinum Available' }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Progress -->
                                    <div class="text-right">
                                        <div class="text-lg font-bold" :class="getProgressColor(game.progress)">
                                            {{ game.progress }}%
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ game.trophies.bronze.earned }}/{{ game.trophies.silver.earned }}/{{ game.trophies.gold.earned }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Trophy Progress Bar -->
                                <div class="mt-2 h-2 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all"
                                        :style="{ width: game.progress + '%' }"
                                    ></div>
                                </div>

                                <!-- Local Match -->
                                <div v-if="game.local_match" class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-green-600 dark:text-green-400 font-medium text-sm">Matched:</span>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ game.local_match.title }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span v-if="game.local_match.difficulty" class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 px-2 py-0.5 rounded">
                                                {{ game.local_match.difficulty }}/10
                                            </span>
                                            <span v-if="game.local_match.time_range" class="text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 px-2 py-0.5 rounded">
                                                {{ game.local_match.time_range }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-if="game.local_match.has_guide" class="mt-2 flex gap-2">
                                        <a
                                            v-if="game.local_match.playstationtrophies_url"
                                            :href="game.local_match.playstationtrophies_url"
                                            target="_blank"
                                            class="text-xs text-primary-600 hover:text-primary-800 flex items-center gap-1"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                            PS Trophies Guide
                                        </a>
                                        <a
                                            v-if="game.local_match.powerpyx_url"
                                            :href="game.local_match.powerpyx_url"
                                            target="_blank"
                                            class="text-xs text-orange-600 hover:text-orange-800 flex items-center gap-1"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                            PowerPyx Guide
                                        </a>
                                    </div>
                                </div>

                                <!-- No Match -->
                                <div v-else class="mt-3 p-2 bg-gray-50 dark:bg-slate-700/50 rounded-lg border border-gray-200 dark:border-slate-600 text-sm text-gray-500 dark:text-gray-400">
                                    Not matched to local database
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    show: Boolean
})

const emit = defineEmits(['close'])

const username = ref('')
const loading = ref(false)
const error = ref('')
const userData = ref(null)
const filter = ref('all')

const filteredGames = computed(() => {
    if (!userData.value) return []

    let games = userData.value.games

    switch (filter.value) {
        case 'unplatinumed':
            return games.filter(g => g.has_platinum && !g.earned_platinum)
        case 'with_guide':
            return games.filter(g => g.local_match?.has_guide)
        case 'no_match':
            return games.filter(g => !g.local_match)
        default:
            return games
    }
})

const getProgressColor = (progress) => {
    if (progress === 100) return 'text-green-600'
    if (progress >= 75) return 'text-blue-600'
    if (progress >= 50) return 'text-yellow-600'
    return 'text-gray-600 dark:text-gray-400'
}

const lookupUser = async () => {
    if (!username.value.trim()) return

    loading.value = true
    error.value = ''
    userData.value = null

    try {
        const response = await fetch(`/api/admin/psn/lookup/${encodeURIComponent(username.value.trim())}`)
        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.message || 'Failed to lookup user')
        }

        userData.value = data
        filter.value = 'all'
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}
</script>
