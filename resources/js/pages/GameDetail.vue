<template>
    <AppLayout>
        <!-- Loading -->
        <div v-if="loading" class="flex justify-center items-center h-96">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="max-w-4xl mx-auto px-4 py-16 text-center">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Game Not Found</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-8">{{ error }}</p>
            <router-link to="/" class="text-primary-600 hover:underline">Back to Home</router-link>
        </div>

        <!-- Game Content -->
        <div v-else-if="game">
            <!-- Hero Banner -->
            <div
                v-if="game.banner_url"
                class="relative h-64 md:h-80 lg:h-96 bg-cover bg-center"
                :style="{ backgroundImage: `url(${game.banner_url})` }"
            >
                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/60 to-transparent"></div>

                <!-- Content -->
                <div class="absolute inset-0 flex items-end">
                    <div class="max-w-6xl mx-auto px-4 pb-6 w-full">
                        <!-- Breadcrumb -->
                        <nav class="mb-3 text-sm">
                            <router-link to="/" class="text-gray-300 hover:text-white">Home</router-link>
                            <span class="mx-2 text-gray-500">/</span>
                            <span class="text-gray-400">{{ game.title }}</span>
                        </nav>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white drop-shadow-lg">
                            {{ game.title }}
                        </h1>
                    </div>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4 py-8">
                <!-- Breadcrumb (only if no banner) -->
                <nav v-if="!game.banner_url" class="mb-6 text-sm">
                    <router-link to="/" class="text-primary-600 hover:underline">Home</router-link>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ game.title }}</span>
                </nav>

            <!-- Header Section -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden mb-8">
                <div class="md:flex">
                    <!-- Cover Image -->
                    <div class="md:w-1/3 lg:w-1/4">
                        <img
                            v-if="game.cover_url"
                            :src="game.cover_url"
                            :alt="game.title + ' cover'"
                            class="w-full h-auto object-cover"
                        />
                        <div v-else class="w-full h-64 bg-gray-200 dark:bg-slate-700 flex items-center justify-center">
                            <span class="text-gray-400 text-4xl">?</span>
                        </div>
                    </div>

                    <!-- Game Info -->
                    <div class="p-6 md:flex-1">
                        <h1 v-if="!game.banner_url" class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ game.title }}
                        </h1>

                        <!-- Platforms -->
                        <div v-if="game.platforms?.length" class="flex flex-wrap gap-2 mb-4">
                            <span
                                v-for="platform in game.platforms"
                                :key="platform.id"
                                class="h-10 px-2 inline-flex items-center bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 text-sm font-medium rounded"
                            >
                                <PlatformIcon :slug="platform.slug" :fallback="platform.short_name" :label="platform.slug === 'ps-vr' ? 'VR' : ''" size-class="h-8" />
                            </span>
                        </div>

                        <!-- Developer/Publisher -->
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <span v-if="game.developer">{{ game.developer }}</span>
                            <span v-if="game.developer && game.publisher"> / </span>
                            <span v-if="game.publisher">{{ game.publisher }}</span>
                        </div>

                        <!-- Trophy Stats Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ game.difficulty || '?' }}<span class="text-sm font-normal">/10</span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Difficulty</div>
                            </div>
                            <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ game.time_min || '?' }}<span v-if="game.time_max && game.time_max !== game.time_min">-{{ game.time_max }}</span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Hours</div>
                            </div>
                            <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ game.playthroughs_required || '?' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Playthroughs</div>
                            </div>
                            <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold" :class="game.missable_trophies ? 'text-orange-500' : 'text-green-500'">
                                    {{ game.missable_trophies ? 'Yes' : 'No' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Missables</div>
                            </div>
                        </div>

                        <!-- Online Trophies Warning -->
                        <div v-if="game.has_online_trophies" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-4">
                            <span class="text-red-700 dark:text-red-300 text-sm font-medium">
                                This game has online trophies
                            </span>
                        </div>

                        <!-- Genres -->
                        <div v-if="game.genres?.length" class="mb-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400 mr-2">Genres:</span>
                            <span
                                v-for="(genre, i) in game.genres"
                                :key="genre.id"
                                class="text-sm text-gray-700 dark:text-gray-300"
                            >
                                {{ genre.name }}<span v-if="i < game.genres.length - 1">, </span>
                            </span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3">
                            <!-- Add to List Button -->
                            <button
                                @click="toggleList"
                                :disabled="listLoading"
                                :class="[
                                    'inline-flex items-center gap-2 px-5 py-2.5 rounded-lg font-medium transition-all',
                                    inList
                                        ? 'bg-primary-600 text-white hover:bg-primary-700'
                                        : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 hover:bg-primary-100 dark:hover:bg-primary-900/50 hover:text-primary-600 dark:hover:text-primary-400',
                                    listLoading ? 'opacity-50 cursor-wait' : ''
                                ]"
                            >
                                <!-- Loading -->
                                <svg v-if="listLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <!-- Check icon (in list) -->
                                <svg v-else-if="inList" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <!-- Plus icon (not in list) -->
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <span v-if="!isAuthenticated">Sign in to add to list</span>
                                <span v-else-if="inList">In My List</span>
                                <span v-else>Add to My List</span>
                            </button>

                            <!-- Report Issue Button -->
                            <router-link
                                :to="`/report-issue?game=${game.slug}`"
                                class="inline-flex items-center gap-2 px-4 py-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors text-sm"
                                title="Report incorrect information"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="hidden sm:inline">Report Issue</span>
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trophy Guides Section -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Trophy Guides</h2>

                <div v-if="hasGuides" class="grid md:grid-cols-3 gap-4">
                    <!-- PSNProfiles -->
                    <a
                        v-if="game.psnprofiles_url"
                        :href="game.psnprofiles_url"
                        target="_blank"
                        rel="noopener"
                        @click="trackGuideClick('psnprofiles')"
                        class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors"
                    >
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold">
                            P
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">PSNProfiles</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">View Guide</div>
                        </div>
                    </a>

                    <!-- PlayStationTrophies -->
                    <a
                        v-if="game.playstationtrophies_url"
                        :href="game.playstationtrophies_url"
                        target="_blank"
                        rel="noopener"
                        @click="trackGuideClick('playstationtrophies')"
                        class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors"
                    >
                        <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center text-white font-bold">
                            PST
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">PlayStationTrophies</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">View Guide</div>
                        </div>
                    </a>

                    <!-- PowerPyx -->
                    <a
                        v-if="game.powerpyx_url"
                        :href="game.powerpyx_url"
                        target="_blank"
                        rel="noopener"
                        @click="trackGuideClick('powerpyx')"
                        class="flex items-center gap-3 p-4 bg-orange-50 dark:bg-orange-900/30 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/50 transition-colors"
                    >
                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center text-white font-bold">
                            PPX
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">PowerPyx</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">View Guide</div>
                        </div>
                    </a>
                </div>

                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>No trophy guides available yet for this game.</p>
                </div>

                <!-- Guide Voting (only when 2+ guides available) -->
                <div v-if="guideVotes?.voting_enabled" class="mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <!-- Vote Result Banner (when enough votes) -->
                    <div v-if="guideVotes.winner && guideVotes.total_votes >= 3" class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-sm text-green-700 dark:text-green-400 font-medium">
                            {{ guideVotes.winner_percentage }}% of users preferred the {{ guideLabels[guideVotes.winner] }} guide
                        </p>
                    </div>

                    <!-- Voting UI -->
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Which guide did you use?</span>
                        <div class="flex flex-wrap gap-3">
                            <label
                                v-for="guide in guideVotes.available_guides"
                                :key="guide"
                                :class="[
                                    'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-colors text-sm',
                                    userVote === guide
                                        ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500'
                                        : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600',
                                    (!inList || votingLoading) ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                            >
                                <input
                                    type="radio"
                                    name="preferred_guide"
                                    :value="guide"
                                    :checked="userVote === guide"
                                    :disabled="!inList || votingLoading"
                                    @change="voteForGuide(guide)"
                                    class="sr-only"
                                />
                                <span :class="[
                                    'w-4 h-4 rounded-full border-2 flex items-center justify-center',
                                    userVote === guide
                                        ? 'border-primary-500 bg-primary-500'
                                        : 'border-gray-400 dark:border-gray-500'
                                ]">
                                    <span v-if="userVote === guide" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                                {{ guideLabels[guide] }}
                                <span v-if="guideVotes.total_votes > 0" class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ guideVotes.results[guide]?.percentage || 0 }}%)
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Not in list hint -->
                    <p v-if="!inList && isAuthenticated" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Add this game to your list to vote
                    </p>
                    <p v-else-if="!isAuthenticated" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <button @click="loginWithGoogle" class="text-primary-600 dark:text-primary-400 hover:underline">Sign in</button> to vote
                    </p>
                </div>
            </div>

            <!-- Recommendations Section -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Players Also Have</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Based on what other trophy hunters have in their lists
                </p>

                <!-- Loading -->
                <div v-if="loadingRecommendations" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div v-for="n in 6" :key="n" class="animate-pulse">
                        <div class="aspect-[3/4] bg-gray-200 dark:bg-slate-700 rounded-lg mb-2"></div>
                        <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-3/4"></div>
                    </div>
                </div>

                <!-- Recommendations Grid -->
                <div v-else-if="recommendations.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <router-link
                        v-for="rec in recommendations"
                        :key="rec.game_id"
                        :to="`/game/${rec.slug}`"
                        class="group"
                    >
                        <div class="relative aspect-[3/4] bg-gray-200 dark:bg-slate-700 rounded-lg overflow-hidden mb-2">
                            <img
                                v-if="rec.cover_url"
                                :src="rec.cover_url"
                                :alt="rec.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            />
                            <!-- Overlap Percentage Badge -->
                            <div class="absolute top-2 right-2 px-2 py-1 bg-black/70 rounded text-white text-xs font-bold">
                                {{ rec.percentage }}%
                            </div>
                            <!-- Guide Indicator -->
                            <div v-if="rec.has_guide" class="absolute bottom-2 left-2">
                                <span class="px-1.5 py-0.5 bg-green-500 rounded text-white text-[10px] font-bold">
                                    GUIDE
                                </span>
                            </div>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                            {{ rec.title }}
                        </h3>
                        <div class="flex gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <span v-if="rec.difficulty">{{ rec.difficulty }}/10</span>
                            <span v-if="rec.time_min">{{ rec.time_min }}{{ rec.time_max && rec.time_max !== rec.time_min ? `-${rec.time_max}` : '' }}h</span>
                        </div>
                    </router-link>
                </div>

                <!-- No Recommendations -->
                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>Not enough data yet to show recommendations.</p>
                    <button
                        v-if="!inList"
                        @click="toggleList"
                        :disabled="listLoading"
                        class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors disabled:opacity-50"
                    >
                        <svg v-if="listLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        {{ isAuthenticated ? 'Add to My List' : 'Sign in to add' }}
                    </button>
                    <p v-else class="text-sm mt-1">Thanks for adding! Recommendations improve as more players add games.</p>
                </div>
            </div>

            <!-- Description -->
            <div v-if="game.description" class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">About</h2>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ game.description }}</p>
            </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import { useAuth } from '../composables/useAuth'
import { useAppConfig } from '../composables/useAppConfig'
import { useUserGames } from '../composables/useUserGames'
import { apiPost } from '../utils/api'
import AppLayout from '../components/AppLayout.vue'
import PlatformIcon from '../components/PlatformIcon.vue'

const { appName } = useAppConfig()

const route = useRoute()
const { isAuthenticated, loginWithGoogle } = useAuth()
const { addToList, removeFromList, checkInList, updatePreferredGuide } = useUserGames()

const game = ref(null)
const loading = ref(true)
const error = ref(null)
const recommendations = ref([])
const loadingRecommendations = ref(false)
const inList = ref(false)
const listLoading = ref(false)

// Guide voting
const guideVotes = ref(null)
const userVote = ref(null)
const votingLoading = ref(false)

const guideLabels = {
    psnprofiles: 'PSNProfiles',
    playstationtrophies: 'PlayStationTrophies',
    powerpyx: 'PowerPyx',
}

const hasGuides = computed(() => {
    return game.value?.psnprofiles_url || game.value?.playstationtrophies_url || game.value?.powerpyx_url
})

function trackGuideClick(source) {
    if (game.value) {
        apiPost('/guide-clicks', { game_id: game.value.id, guide_source: source })
    }
}

// Dynamic SEO meta tags
useHead(() => {
    if (!game.value) {
        return {
            title: `Loading... | ${appName}`,
        }
    }

    const title = `${game.value.title} Trophy Guide | ${appName}`
    const description = buildDescription()
    const image = game.value.cover_url || game.value.banner_url

    return {
        title,
        meta: [
            { name: 'description', content: description },
            // Open Graph
            { property: 'og:title', content: title },
            { property: 'og:description', content: description },
            { property: 'og:type', content: 'website' },
            { property: 'og:url', content: window.location.href },
            ...(image ? [{ property: 'og:image', content: image }] : []),
            // Twitter
            { name: 'twitter:card', content: 'summary_large_image' },
            { name: 'twitter:title', content: title },
            { name: 'twitter:description', content: description },
            ...(image ? [{ name: 'twitter:image', content: image }] : []),
        ],
        // JSON-LD Structured Data
        script: [
            {
                type: 'application/ld+json',
                innerHTML: JSON.stringify({
                    '@context': 'https://schema.org',
                    '@type': 'VideoGame',
                    name: game.value.title,
                    description: game.value.description || description,
                    image: image,
                    gamePlatform: game.value.platforms?.map(p => p.name) || [],
                    genre: game.value.genres?.map(g => g.name) || [],
                    publisher: game.value.publisher || undefined,
                    developer: { '@type': 'Organization', name: game.value.developer } || undefined,
                }),
            },
        ],
    }
})

function buildDescription() {
    if (!game.value) return ''

    const parts = [`Trophy guide for ${game.value.title}.`]

    if (game.value.difficulty) {
        parts.push(`Difficulty: ${game.value.difficulty}/10.`)
    }
    if (game.value.time_min) {
        const time = game.value.time_max && game.value.time_max !== game.value.time_min
            ? `${game.value.time_min}-${game.value.time_max}`
            : game.value.time_min
        parts.push(`Time: ${time} hours.`)
    }
    if (game.value.playthroughs_required) {
        parts.push(`${game.value.playthroughs_required} playthrough${game.value.playthroughs_required > 1 ? 's' : ''} required.`)
    }

    const guideCount = [game.value.psnprofiles_url, game.value.playstationtrophies_url, game.value.powerpyx_url].filter(Boolean).length
    if (guideCount > 0) {
        parts.push(`${guideCount} guide${guideCount > 1 ? 's' : ''} available.`)
    }

    return parts.join(' ')
}

async function fetchGame() {
    loading.value = true
    error.value = null

    try {
        const response = await fetch(`/api/games/${route.params.slug}`)
        if (!response.ok) {
            throw new Error('Game not found')
        }
        game.value = await response.json()
        // Fetch recommendations, guide votes, and check list status after game loads
        fetchRecommendations()
        fetchGuideVotes()
        checkListStatus()
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

async function checkListStatus() {
    if (!isAuthenticated.value || !game.value) return

    try {
        const result = await checkInList(game.value.id)
        inList.value = result.in_list
        userVote.value = result.preferred_guide || null
    } catch (e) {
        console.error('Failed to check list status:', e)
    }
}

async function fetchGuideVotes() {
    if (!game.value) return

    try {
        const response = await fetch(`/api/games/${game.value.slug}/guide-votes`)
        if (response.ok) {
            guideVotes.value = await response.json()
        }
    } catch (e) {
        console.error('Failed to fetch guide votes:', e)
    }
}

async function voteForGuide(guide) {
    if (!isAuthenticated.value) {
        loginWithGoogle()
        return
    }

    if (!inList.value || votingLoading.value) return

    votingLoading.value = true
    try {
        await updatePreferredGuide(game.value.id, guide)
        userVote.value = guide
        // Refresh vote counts
        await fetchGuideVotes()
    } catch (e) {
        console.error('Failed to vote:', e)
    } finally {
        votingLoading.value = false
    }
}

async function toggleList() {
    if (!isAuthenticated.value) {
        loginWithGoogle()
        return
    }

    if (listLoading.value || !game.value) return

    listLoading.value = true
    try {
        if (inList.value) {
            await removeFromList(game.value.id)
            inList.value = false
        } else {
            await addToList(game.value.id)
            inList.value = true
        }
    } catch (e) {
        console.error('Failed to update list:', e)
    } finally {
        listLoading.value = false
    }
}

async function fetchRecommendations() {
    if (!game.value) return

    loadingRecommendations.value = true
    try {
        const response = await fetch(`/api/games/${game.value.slug}/recommendations`)
        if (response.ok) {
            const data = await response.json()
            recommendations.value = data.recommendations || []
        }
    } catch (e) {
        console.error('Failed to fetch recommendations:', e)
        recommendations.value = []
    } finally {
        loadingRecommendations.value = false
    }
}

onMounted(fetchGame)

// Re-fetch when route changes
watch(() => route.params.slug, () => {
    recommendations.value = []
    inList.value = false
    guideVotes.value = null
    userVote.value = null
    fetchGame()
})

// Check list status when auth state changes
watch(isAuthenticated, (newVal) => {
    if (newVal && game.value) {
        checkListStatus()
    } else {
        inList.value = false
    }
})
</script>
