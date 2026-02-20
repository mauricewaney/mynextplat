<template>
    <div
        class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:shadow-md dark:shadow-slate-900/50 transition-all duration-300 flex gap-3 sm:gap-4 p-3 sm:p-4 select-none cursor-pointer"
        @click="navigateToGame"
    >
        <!-- Unobtainable Stamp Overlay -->
        <div
            v-if="game.is_unobtainable"
            class="absolute inset-0 flex items-center justify-center pointer-events-none z-[5] overflow-hidden rounded-xl"
        >
            <div class="unobtainable-stamp">
                UNOBTAINABLE
            </div>
        </div>
        <!-- Cover Image -->
        <div class="relative w-28 sm:w-28 h-40 sm:h-36 shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-600 rounded-lg overflow-hidden">
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
                <AddToListButton :game-id="game.id" @removed="$emit('removed', game.id)" />
            </div>
            <!-- Admin Edit Button -->
            <a
                v-if="isAdmin"
                :href="`/admin/games?edit=${game.id}`"
                target="_blank"
                @click.stop
                class="absolute bottom-1 right-1 p-1.5 bg-black/60 hover:bg-black/80 text-white rounded-md opacity-0 group-hover:opacity-100 transition-opacity"
                title="Edit in Admin"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>
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
                        class="h-8 px-1.5 inline-flex items-center bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded"
                    >
                        <PlatformIcon :slug="platform.slug" :fallback="platform.short_name || platform.name" :label="platform.slug === 'ps-vr' ? 'VR' : ''" size-class="h-6" />
                    </span>
                </div>
                <!-- Spacer -->
                <div class="flex-1"></div>
                <!-- Status Dropdown (when in user's library) -->
                <div v-if="game.user_status" class="relative status-dropdown-container shrink-0" @click.stop>
                    <button
                        @click="showStatusMenu = !showStatusMenu"
                        :class="[
                            'flex items-center gap-1 px-2 py-1 text-[10px] sm:text-xs font-medium rounded-lg transition-all duration-200',
                            'hover:ring-2 hover:ring-offset-1 hover:ring-offset-white dark:hover:ring-offset-slate-800',
                            statusStyles[game.user_status]?.button || 'bg-gray-100 text-gray-700'
                        ]"
                    >
                        <span :class="['w-1.5 h-1.5 rounded-full', statusStyles[game.user_status]?.dot]"></span>
                        {{ statusLabels[game.user_status] }}
                        <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <!-- Dropdown Menu -->
                    <Transition name="dropdown">
                        <div
                            v-if="showStatusMenu"
                            class="absolute right-0 top-full mt-1 z-50 min-w-[140px] bg-white dark:bg-slate-800 rounded-xl shadow-lg ring-1 ring-black/5 dark:ring-white/10 overflow-hidden"
                        >
                            <div class="py-1">
                                <button
                                    v-for="status in statusOptions"
                                    :key="status.value"
                                    @click="selectStatus(status.value)"
                                    :class="[
                                        'w-full flex items-center gap-2.5 px-3 py-2 text-xs transition-colors',
                                        game.user_status === status.value
                                            ? 'bg-gray-50 dark:bg-slate-700/50'
                                            : 'hover:bg-gray-50 dark:hover:bg-slate-700/50'
                                    ]"
                                >
                                    <span :class="['w-2 h-2 rounded-full ring-2 ring-offset-1 ring-offset-white dark:ring-offset-slate-800', status.dot, status.ring]"></span>
                                    <span :class="['font-medium', game.user_status === status.value ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300']">
                                        {{ status.label }}
                                    </span>
                                    <svg v-if="game.user_status === status.value" class="w-3.5 h-3.5 ml-auto text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
                <!-- Scores (always reserve space) -->
                <component
                    :is="game.igdb_id ? 'a' : 'div'"
                    v-bind="game.igdb_id ? { href: `https://www.igdb.com/games/${game.slug}`, target: '_blank', rel: 'noopener' } : {}"
                    class="flex items-center gap-1 shrink-0"
                    @click.stop
                >
                    <!-- User Score -->
                    <div class="group/user relative inline-flex" @click.stop="toggleTooltip('user')">
                        <div
                            :class="[
                                'w-7 h-7 sm:w-8 sm:h-8 rounded-md sm:rounded-lg flex items-center justify-center font-bold',
                                displayUserScore !== null ? ['text-white', userScoreClass] : 'bg-gray-200 dark:bg-slate-700 text-gray-400 dark:text-gray-500',
                                displayUserScore === 'N/A' || displayUserScore === null ? 'text-[9px] sm:text-[10px]' : 'text-sm sm:text-sm'
                            ]"
                        >
                            {{ displayUserScore ?? '--' }}
                        </div>
                        <div
                            :class="[
                                'absolute bottom-full right-0 mb-1.5 px-2 py-1 whitespace-nowrap bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 text-xs rounded shadow-lg ring-1 ring-black/5 dark:ring-white/10 pointer-events-none z-50 transition-opacity duration-150',
                                showTooltip === 'user' ? 'opacity-100' : 'opacity-0 hidden group-hover/user:block group-hover/user:opacity-100'
                            ]"
                        >
                            <template v-if="displayUserScore === null">No user score</template>
                            <template v-else-if="displayUserScore === 'N/A'">Not enough IGDB user ratings</template>
                            <template v-else>IGDB User Score ({{ game.user_score_count }} ratings)</template>
                        </div>
                    </div>
                    <!-- Critic Score -->
                    <div class="group/critic relative inline-flex" @click.stop="toggleTooltip('critic')">
                        <div
                            :class="[
                                'w-6 h-6 sm:w-7 sm:h-7 rounded-md sm:rounded-lg flex items-center justify-center font-bold border',
                                displayCriticScore !== null ? criticScoreClass : 'border-gray-200 dark:border-slate-600 text-gray-400 dark:text-gray-500',
                                displayCriticScore === 'N/A' || displayCriticScore === null ? 'text-[9px] sm:text-[10px]' : 'text-xs sm:text-xs'
                            ]"
                        >
                            {{ displayCriticScore ?? '--' }}
                        </div>
                        <div
                            :class="[
                                'absolute bottom-full right-0 mb-1.5 px-2 py-1 whitespace-nowrap bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 text-xs rounded shadow-lg ring-1 ring-black/5 dark:ring-white/10 pointer-events-none z-50 transition-opacity duration-150',
                                showTooltip === 'critic' ? 'opacity-100' : 'opacity-0 hidden group-hover/critic:block group-hover/critic:opacity-100'
                            ]"
                        >
                            <template v-if="displayCriticScore === null">No critic score</template>
                            <template v-else-if="displayCriticScore === 'N/A'">Not enough IGDB critic reviews</template>
                            <template v-else>IGDB Critic Score ({{ game.critic_score_count }} sources)</template>
                        </div>
                    </div>
                </component>
            </div>

            <!-- Developer/Publisher -->
            <p class="text-[10px] sm:text-sm text-gray-500 dark:text-gray-400 mb-1 sm:mb-1.5 line-clamp-1">
                {{ game.developer || game.publisher || 'Unknown Developer' }}
            </p>

            <!-- Stats Group -->
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-md sm:rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 flex-1">
                <!-- Mobile: 2 rows, stacked label below value -->
                <div class="grid grid-cols-3 gap-x-2 gap-y-2 sm:hidden text-xs">
                    <div>
                        <div v-if="game.difficulty" :class="['font-bold', difficultyTextClass]">{{ game.difficulty }}/10</div>
                        <div v-else class="font-bold text-gray-300 dark:text-gray-600">--</div>
                        <div class="text-gray-500 dark:text-gray-400 text-[10px]">Difficulty</div>
                    </div>
                    <div>
                        <div v-if="timeValues" class="font-bold text-gray-700 dark:text-gray-300">{{ timeValues.mobile }}</div>
                        <div v-else class="font-bold text-gray-300 dark:text-gray-600">--</div>
                        <div class="text-gray-500 dark:text-gray-400 text-[10px]">Time</div>
                    </div>
                    <div>
                        <div v-if="game.playthroughs_required" class="font-bold text-gray-700 dark:text-gray-300">{{ game.playthroughs_required }}x</div>
                        <div v-else class="font-bold text-gray-300 dark:text-gray-600">--</div>
                        <div class="text-gray-500 dark:text-gray-400 text-[10px]">Runs</div>
                    </div>
                    <div>
                        <div v-if="game.missable_trophies === false" class="font-bold text-emerald-600 dark:text-emerald-400">No</div>
                        <div v-else-if="game.missable_trophies === true" class="font-bold text-amber-600 dark:text-amber-400">Yes</div>
                        <div v-else class="font-bold text-gray-300 dark:text-gray-600">--</div>
                        <div class="text-gray-500 dark:text-gray-400 text-[10px]">Missables</div>
                    </div>
                    <div>
                        <div v-if="game.has_online_trophies === false" class="font-bold text-emerald-600 dark:text-emerald-400">No</div>
                        <div v-else-if="game.has_online_trophies === true" class="font-bold text-red-600 dark:text-red-400">Yes</div>
                        <div v-else class="font-bold text-gray-300 dark:text-gray-600">--</div>
                        <div class="text-gray-500 dark:text-gray-400 text-[10px]">Online</div>
                    </div>
                    <div v-if="game.data_source">
                        <div :class="['font-bold', dataSourceClass]">{{ dataSourceLabel }}</div>
                        <div class="text-gray-500 dark:text-gray-400 text-[10px]">Source</div>
                    </div>
                </div>
                <!-- Desktop: original label-value rows -->
                <div class="hidden sm:grid grid-cols-2 gap-x-2 gap-y-1.5 text-sm">
                    <!-- Difficulty -->
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 w-20 shrink-0">Difficulty</span>
                        <div v-if="game.difficulty" class="flex items-center gap-1">
                            <div class="w-12 h-1.5 bg-gray-200 dark:bg-slate-600 rounded-full overflow-hidden">
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
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 w-20 shrink-0">Missables</span>
                        <span v-if="game.missable_trophies === false" class="text-emerald-600 dark:text-emerald-400 font-medium">None</span>
                        <span v-else-if="game.missable_trophies === true" class="text-amber-600 dark:text-amber-400 font-medium">Yes</span>
                        <span v-else class="text-gray-300 dark:text-gray-600">--</span>
                    </div>

                    <!-- Time -->
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 w-20 shrink-0">Time</span>
                        <span v-if="timeValues" class="font-medium text-gray-700 dark:text-gray-300">{{ timeValues.desktop }}</span>
                        <span v-else class="text-gray-300 dark:text-gray-600">--</span>
                    </div>

                    <!-- Playthroughs -->
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 w-20 shrink-0">Runs</span>
                        <span v-if="game.playthroughs_required" class="font-medium text-gray-700 dark:text-gray-300">{{ game.playthroughs_required }}x</span>
                        <span v-else class="text-gray-300 dark:text-gray-600">--</span>
                    </div>

                    <!-- Online -->
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 w-20 shrink-0">Online</span>
                        <span v-if="game.has_online_trophies === false" class="text-emerald-600 dark:text-emerald-400 font-medium">No</span>
                        <span v-else-if="game.has_online_trophies === true" class="text-red-600 dark:text-red-400 font-medium">Yes</span>
                        <span v-else class="text-gray-300 dark:text-gray-600">--</span>
                    </div>

                    <!-- Source -->
                    <div v-if="game.data_source" class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 w-20 shrink-0">Source</span>
                        <span :class="['font-medium', dataSourceClass]">{{ dataSourceLabel }}</span>
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
                            @click.stop="trackGuideClick('psnprofiles')"
                            class="px-1.5 sm:px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 text-[10px] sm:text-xs font-bold hover:bg-blue-200 dark:hover:bg-blue-900/70 transition-colors"
                            title="PSNProfiles Guide"
                        >PSNP</a>
                        <a
                            v-if="game.playstationtrophies_url"
                            :href="game.playstationtrophies_url"
                            target="_blank"
                            @click.stop="trackGuideClick('playstationtrophies')"
                            class="px-1.5 sm:px-2 py-0.5 rounded bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 text-[10px] sm:text-xs font-bold hover:bg-purple-200 dark:hover:bg-purple-900/70 transition-colors"
                            title="PlayStationTrophies Guide"
                        >PST</a>
                        <a
                            v-if="game.powerpyx_url"
                            :href="game.powerpyx_url"
                            target="_blank"
                            @click.stop="trackGuideClick('powerpyx')"
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { apiPost } from '../utils/api'
import { useAuth } from '../composables/useAuth'
import AddToListButton from './AddToListButton.vue'
import PlatformIcon from './PlatformIcon.vue'

const router = useRouter()
const { isAdmin } = useAuth()

function navigateToGame() {
    router.push('/game/' + props.game.slug)
}

const props = defineProps({
    game: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['update-status', 'removed'])

// Status dropdown state
const showStatusMenu = ref(false)

// Tooltip state (mobile tap)
const showTooltip = ref(null)
let tooltipTimer = null

function toggleTooltip(type) {
    if (showTooltip.value === type) {
        showTooltip.value = null
    } else {
        showTooltip.value = type
        clearTimeout(tooltipTimer)
        tooltipTimer = setTimeout(() => { showTooltip.value = null }, 2000)
    }
}

const statusLabels = {
    backlog: 'Backlog',
    in_progress: 'Playing',
    completed: '100%',
    platinumed: 'Platinum',
    abandoned: 'Dropped',
}

const statusStyles = {
    backlog: {
        button: 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:ring-slate-300 dark:hover:ring-slate-500',
        dot: 'bg-slate-400 dark:bg-slate-500',
    },
    in_progress: {
        button: 'bg-sky-50 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 hover:ring-sky-300 dark:hover:ring-sky-600',
        dot: 'bg-sky-500 dark:bg-sky-400',
    },
    completed: {
        button: 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 hover:ring-emerald-300 dark:hover:ring-emerald-600',
        dot: 'bg-emerald-500 dark:bg-emerald-400',
    },
    platinumed: {
        button: 'bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 hover:ring-amber-300 dark:hover:ring-amber-600',
        dot: 'bg-amber-500 dark:bg-amber-400',
    },
    abandoned: {
        button: 'bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 hover:ring-rose-300 dark:hover:ring-rose-600',
        dot: 'bg-rose-500 dark:bg-rose-400',
    },
}

const statusOptions = [
    { value: 'backlog', label: 'Backlog', dot: 'bg-slate-400', ring: 'ring-slate-300 dark:ring-slate-500' },
    { value: 'in_progress', label: 'Playing', dot: 'bg-sky-500', ring: 'ring-sky-300 dark:ring-sky-500' },
    { value: 'completed', label: '100%', dot: 'bg-emerald-500', ring: 'ring-emerald-300 dark:ring-emerald-500' },
    { value: 'platinumed', label: 'Platinum', dot: 'bg-amber-500', ring: 'ring-amber-300 dark:ring-amber-500' },
    { value: 'abandoned', label: 'Dropped', dot: 'bg-rose-500', ring: 'ring-rose-300 dark:ring-rose-500' },
]

function selectStatus(status) {
    showStatusMenu.value = false
    if (status !== props.game.user_status) {
        emit('update-status', props.game.id, status)
    }
}

function handleClickOutside(e) {
    if (!e.target.closest('.status-dropdown-container')) {
        showStatusMenu.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
})

// Minimum rating counts to display (filter out unreliable scores)
const MIN_USER_RATINGS = 3
const MIN_CRITIC_SOURCES = 3

const displayUserScore = computed(() => {
    if (!props.game.user_score) return null
    const count = props.game.user_score_count
    // Count known and below threshold → unreliable
    if (count != null && count < MIN_USER_RATINGS) return 'N/A'
    return props.game.user_score
})

const displayCriticScore = computed(() => {
    if (!props.game.critic_score) return null
    const count = props.game.critic_score_count
    // Count known and below threshold → unreliable
    if (count != null && count < MIN_CRITIC_SOURCES) return 'N/A'
    return props.game.critic_score
})

const userScoreClass = computed(() => {
    const s = displayUserScore.value
    if (s === 'N/A') return 'bg-gray-400 dark:bg-gray-600'
    if (s >= 75) return 'bg-emerald-500'
    if (s >= 50) return 'bg-yellow-500'
    return 'bg-red-500'
})

const criticScoreClass = computed(() => {
    const s = displayCriticScore.value
    if (s === 'N/A') return 'border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500'
    if (s >= 75) return 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
    if (s >= 50) return 'border-yellow-500 text-yellow-600 dark:text-yellow-400'
    return 'border-red-500 text-red-600 dark:text-red-400'
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

const dataSourceLabel = computed(() => {
    const s = props.game.data_source
    if (s === 'playstationtrophies') return 'ps trophies'
    if (s === 'powerpyx') return 'powerpyx'
    if (s === 'psnprofiles') return 'psnprofiles'
    return s
})

const dataSourceClass = computed(() => {
    const s = props.game.data_source
    if (s === 'playstationtrophies') return 'text-purple-600 dark:text-purple-400'
    if (s === 'powerpyx') return 'text-orange-500 dark:text-orange-400'
    if (s === 'psnprofiles') return 'text-blue-600 dark:text-blue-400'
    return 'text-gray-600 dark:text-gray-400'
})

const hasGuide = computed(() => {
    return props.game.psnprofiles_url || props.game.playstationtrophies_url || props.game.powerpyx_url
})

function trackGuideClick(source) {
    apiPost('/guide-clicks', { game_id: props.game.id, guide_source: source })
}
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.15s ease-out;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px) scale(0.95);
}

/* Unobtainable Stamp */
.unobtainable-stamp {
    font-family: 'Arial Black', 'Helvetica Bold', sans-serif;
    font-size: clamp(0.875rem, 3.5vw, 1.5rem);
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #dc2626;
    border: 3px solid #dc2626;
    border-radius: 4px;
    padding: 0.3em 0.6em;
    transform: rotate(-12deg);
    opacity: 0.9;
    text-transform: uppercase;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(1px);
    box-shadow:
        inset 0 0 0 2px transparent,
        inset 0 0 0 3px rgba(220, 38, 38, 0.3),
        0 0 20px rgba(220, 38, 38, 0.2);
    text-shadow:
        1px 1px 0 rgba(255, 255, 255, 0.5),
        -1px -1px 0 rgba(0, 0, 0, 0.1);
}

:deep(.dark) .unobtainable-stamp,
.dark .unobtainable-stamp {
    color: #f87171;
    border-color: #f87171;
    background: rgba(0, 0, 0, 0.2);
    box-shadow:
        inset 0 0 0 2px transparent,
        inset 0 0 0 3px rgba(248, 113, 113, 0.3),
        0 0 20px rgba(248, 113, 113, 0.3);
    text-shadow:
        1px 1px 0 rgba(0, 0, 0, 0.5),
        -1px -1px 0 rgba(255, 255, 255, 0.1);
}
</style>
