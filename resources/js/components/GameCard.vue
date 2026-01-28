<template>
    <div
        class="group bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:shadow-md dark:shadow-slate-900/50 transition-all duration-300 flex gap-2 sm:gap-4 p-2 sm:p-4"
    >
        <!-- Cover Image -->
        <div class="relative w-20 sm:w-28 h-28 sm:h-36 shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-600 rounded-lg overflow-hidden">
            <img
                v-if="game.cover_url"
                :src="game.cover_url"
                :alt="game.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                loading="lazy"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <!-- Add to List Button -->
            <div class="absolute top-1 right-1">
                <AddToListButton :game-id="game.id" />
            </div>
        </div>

        <!-- Info Section -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Row: Title, Platforms, Score -->
            <div class="flex items-start gap-1 sm:gap-2">
                <!-- Title -->
                <router-link
                    :to="'/game/' + game.slug"
                    class="font-semibold text-gray-900 dark:text-white text-xs sm:text-base leading-tight line-clamp-1 hover:text-primary-600 dark:hover:text-primary-400 hover:underline transition-colors"
                >
                    {{ game.title }}
                </router-link>
                <!-- Platforms (hidden on mobile) -->
                <div v-if="game.platforms?.length" class="hidden sm:flex gap-1 shrink-0">
                    <span
                        v-for="platform in game.platforms.slice(0, 3)"
                        :key="platform.id"
                        class="px-1.5 py-0.5 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded"
                    >
                        {{ platform.short_name || platform.name }}
                    </span>
                </div>
                <!-- Spacer -->
                <div class="flex-1"></div>
                <!-- Critic Score -->
                <div
                    v-if="game.critic_score"
                    :class="[
                        'shrink-0 w-6 h-6 sm:w-8 sm:h-8 rounded-md sm:rounded-lg flex items-center justify-center text-xs sm:text-sm font-bold text-white',
                        scoreClass
                    ]"
                >
                    {{ game.critic_score }}
                </div>
            </div>

            <!-- Developer/Publisher -->
            <p class="text-[10px] sm:text-sm text-gray-500 dark:text-gray-400 mb-1 sm:mb-1.5 line-clamp-1">
                {{ game.developer || game.publisher || 'Unknown Developer' }}
            </p>

            <!-- Stats Group -->
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-md sm:rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 flex-1">
                <div class="grid grid-cols-2 gap-x-1 sm:gap-x-2 gap-y-0.5 sm:gap-y-1.5 text-[10px] sm:text-sm">
                    <!-- Difficulty -->
                    <div class="flex items-center gap-1 sm:gap-2">
                        <span class="text-gray-400 dark:text-gray-500 w-14 sm:w-20 shrink-0">Difficulty</span>
                        <div v-if="game.difficulty" class="flex items-center gap-1">
                            <div class="hidden sm:block w-12 h-1.5 bg-gray-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                <div
                                    :class="['h-full rounded-full', difficultyBarClass]"
                                    :style="{ width: `${game.difficulty * 10}%` }"
                                ></div>
                            </div>
                            <span :class="['font-medium', difficultyTextClass]">{{ game.difficulty }}/10</span>
                        </div>
                        <span v-else class="text-gray-300 dark:text-gray-600">--</span>
                    </div>

                    <!-- Missables -->
                    <div class="flex items-center gap-1 sm:gap-2">
                        <span class="text-gray-400 dark:text-gray-500 w-14 sm:w-20 shrink-0">Missables</span>
                        <span v-if="game.missable_trophies === false" class="text-emerald-600 dark:text-emerald-400 font-medium">None</span>
                        <span v-else-if="game.missable_trophies === true" class="text-amber-600 dark:text-amber-400 font-medium">Yes</span>
                        <span v-else class="text-gray-300 dark:text-gray-600">--</span>
                    </div>

                    <!-- Time -->
                    <div class="flex items-center gap-1 sm:gap-2">
                        <span class="text-gray-400 dark:text-gray-500 w-14 sm:w-20 shrink-0">Time</span>
                        <template v-if="timeValues">
                            <span class="font-medium text-gray-700 dark:text-gray-300 sm:hidden">{{ timeValues.mobile }}</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300 hidden sm:inline">{{ timeValues.desktop }}</span>
                        </template>
                        <span v-else class="text-gray-300 dark:text-gray-600">--</span>
                    </div>

                    <!-- Playthroughs -->
                    <div class="flex items-center gap-1 sm:gap-2">
                        <span class="text-gray-400 dark:text-gray-500 w-14 sm:w-20 shrink-0">Runs</span>
                        <span v-if="game.playthroughs_required" class="font-medium text-gray-700 dark:text-gray-300">{{ game.playthroughs_required }}x</span>
                        <span v-else class="text-gray-300 dark:text-gray-600">--</span>
                    </div>

                    <!-- Online -->
                    <div class="flex items-center gap-1 sm:gap-2">
                        <span class="text-gray-400 dark:text-gray-500 w-14 sm:w-20 shrink-0">Online</span>
                        <span v-if="game.has_online_trophies === false" class="text-emerald-600 dark:text-emerald-400 font-medium">No</span>
                        <span v-else-if="game.has_online_trophies === true" class="text-red-600 dark:text-red-400 font-medium">Yes</span>
                        <span v-else class="text-gray-300 dark:text-gray-600">--</span>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Genres, Guides -->
            <div class="flex items-center gap-1 sm:gap-2 mt-1 sm:mt-2 flex-wrap">
                <!-- Genres (hidden on mobile) -->
                <template v-if="game.genres?.length">
                    <span
                        v-for="genre in game.genres.slice(0, 2)"
                        :key="genre.id"
                        class="hidden sm:inline px-2 py-0.5 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 text-xs rounded-full"
                    >
                        {{ genre.name }}
                    </span>
                </template>

                <!-- Spacer -->
                <div class="flex-1"></div>

                <!-- Guide Links -->
                <div v-if="hasGuide" class="flex items-center gap-1.5 px-2 py-1 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-600">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <div class="flex items-center gap-1">
                        <a
                            v-if="game.psnprofiles_url"
                            :href="game.psnprofiles_url"
                            target="_blank"
                            @click.stop
                            class="px-1.5 sm:px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 text-[10px] sm:text-xs font-bold hover:bg-blue-200 dark:hover:bg-blue-900/70 transition-colors"
                            title="PSNProfiles Guide"
                        >PSNP</a>
                        <a
                            v-if="game.playstationtrophies_url"
                            :href="game.playstationtrophies_url"
                            target="_blank"
                            @click.stop
                            class="px-1.5 sm:px-2 py-0.5 rounded bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 text-[10px] sm:text-xs font-bold hover:bg-purple-200 dark:hover:bg-purple-900/70 transition-colors"
                            title="PlayStationTrophies Guide"
                        >PST</a>
                        <a
                            v-if="game.powerpyx_url"
                            :href="game.powerpyx_url"
                            target="_blank"
                            @click.stop
                            class="px-1.5 sm:px-2 py-0.5 rounded bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400 text-[10px] sm:text-xs font-bold hover:bg-orange-200 dark:hover:bg-orange-900/70 transition-colors"
                            title="PowerPyx Guide"
                        >PPX</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import AddToListButton from './AddToListButton.vue'

const props = defineProps({
    game: {
        type: Object,
        required: true
    }
})

const scoreClass = computed(() => {
    const s = props.game.critic_score
    if (s >= 75) return 'bg-emerald-500'
    if (s >= 50) return 'bg-yellow-500'
    return 'bg-red-500'
})

const difficultyBarClass = computed(() => {
    const d = props.game.difficulty
    if (d <= 2) return 'bg-emerald-400'
    if (d <= 4) return 'bg-green-400'
    if (d <= 6) return 'bg-yellow-400'
    if (d <= 8) return 'bg-orange-400'
    return 'bg-red-400'
})

const difficultyTextClass = computed(() => {
    const d = props.game.difficulty
    if (d <= 2) return 'text-emerald-600'
    if (d <= 4) return 'text-green-600'
    if (d <= 6) return 'text-yellow-600'
    if (d <= 8) return 'text-orange-600'
    return 'text-red-600'
})

// Time display - returns object with mobile and desktop versions
const timeValues = computed(() => {
    const min = props.game.time_min
    const max = props.game.time_max
    if (!min && !max) return null
    if (min === max) return { mobile: `${min}h`, desktop: `${min} hours` }
    if (!min) return { mobile: `~${max}h`, desktop: `~${max} hours` }
    if (!max) return { mobile: `${min}h+`, desktop: `${min}+ hours` }
    return { mobile: `${min}-${max}h`, desktop: `${min}-${max} hours` }
})

const hasGuide = computed(() => {
    return props.game.psnprofiles_url || props.game.playstationtrophies_url || props.game.powerpyx_url
})
</script>
