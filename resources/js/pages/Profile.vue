<template>
    <AppLayout :title="profileUser?.display_name ? `${profileUser.display_name}'s Profile` : 'Profile'">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Loading -->
            <div v-if="loading" class="space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6 animate-pulse">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-gray-200 dark:bg-slate-700 rounded-full"></div>
                        <div class="space-y-2">
                            <div class="h-6 bg-gray-200 dark:bg-slate-700 rounded w-40"></div>
                            <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-24"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Not Found -->
            <div v-else-if="notFound" class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Profile Not Found</h1>
                <p class="text-gray-500 dark:text-gray-400 mb-6">This profile doesn't exist or is private.</p>
                <div class="flex items-center justify-center gap-4">
                    <router-link to="/profiles" class="text-primary-600 dark:text-primary-400 hover:underline">
                        Browse Profiles
                    </router-link>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <router-link to="/" class="text-primary-600 dark:text-primary-400 hover:underline">
                        Browse Games
                    </router-link>
                </div>
            </div>

            <!-- Private Profile -->
            <div v-else-if="isPrivate" class="text-center py-16">
                <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
                    <img
                        v-if="profileUser?.avatar"
                        :src="profileUser.avatar"
                        :alt="profileUser.display_name"
                        class="w-20 h-20 rounded-full"
                        @error="$event.target.style.display='none'"
                    />
                    <svg v-else class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ profileUser?.display_name }}</h1>
                <div class="flex items-center justify-center gap-2 text-gray-500 dark:text-gray-400 mb-6">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>This profile is private</span>
                </div>
                <div class="flex items-center justify-center gap-4">
                    <router-link to="/profiles" class="text-primary-600 dark:text-primary-400 hover:underline">
                        Browse Profiles
                    </router-link>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <router-link to="/" class="text-primary-600 dark:text-primary-400 hover:underline">
                        Browse Games
                    </router-link>
                </div>
            </div>

            <!-- Public Profile -->
            <div v-else>
                <!-- Back to Profiles -->
                <router-link
                    to="/profiles"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 mb-4 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    All Profiles
                </router-link>

                <!-- Profile Header -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6 mb-6">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                        <!-- Avatar -->
                        <div class="w-20 h-20 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center overflow-hidden">
                            <img
                                v-if="profileUser?.avatar && !avatarError"
                                :src="profileUser.avatar"
                                :alt="profileUser.display_name"
                                class="w-20 h-20 rounded-full object-cover"
                                @error="avatarError = true"
                            />
                            <span v-else class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                                {{ profileUser?.display_name?.charAt(0) || '?' }}
                            </span>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 text-center sm:text-left">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ profileUser?.display_name }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Member since {{ profileUser?.member_since }}
                            </p>

                            <!-- Stats -->
                            <div class="flex flex-wrap justify-center sm:justify-start gap-4 mt-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Games</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-amber-500">{{ stats.platinumed || stats.platinum || 0 }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Platinum</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-emerald-500">{{ stats.completed || 0 }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">100%</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-sky-500">{{ stats.in_progress || stats.playing || 0 }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Playing</div>
                                </div>
                            </div>
                        </div>

                        <!-- Owner Actions -->
                        <div v-if="isOwner" class="flex flex-col gap-2">
                            <router-link
                                to="/settings"
                                class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Edit Settings
                            </router-link>
                            <button
                                @click="copyProfileLink"
                                class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors flex items-center gap-2"
                            >
                                <svg v-if="copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                {{ copied ? 'Copied!' : 'Share' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.value"
                        @click="currentStatus = tab.value"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                            currentStatus === tab.value
                                ? 'bg-primary-600 text-white'
                                : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700'
                        ]"
                    >
                        {{ tab.label }}
                        <span
                            v-if="getStatusCount(tab.value) > 0"
                            :class="[
                                'ml-1.5 px-1.5 py-0.5 text-xs rounded',
                                currentStatus === tab.value
                                    ? 'bg-primary-500 text-white'
                                    : 'bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-gray-400'
                            ]"
                        >
                            {{ getStatusCount(tab.value) }}
                        </span>
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="filteredGames.length === 0" class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No games</h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        {{ currentStatus === 'all' ? 'This user hasn\'t added any games yet.' : `No games with this status.` }}
                    </p>
                </div>

                <!-- Game Grid -->
                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <router-link
                        v-for="game in filteredGames"
                        :key="game.id"
                        :to="`/game/${game.slug}`"
                        class="group"
                    >
                        <div class="relative aspect-[3/4] bg-gray-200 dark:bg-slate-700 rounded-lg overflow-hidden mb-2">
                            <img
                                v-if="game.cover_url"
                                :src="game.cover_url"
                                :alt="game.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            />
                            <!-- Status Badge -->
                            <div class="absolute top-2 right-2">
                                <span :class="[
                                    'px-1.5 py-0.5 rounded text-[10px] font-bold uppercase',
                                    statusColors[game.status]
                                ]">
                                    {{ statusLabels[game.status] }}
                                </span>
                            </div>
                            <!-- Guide Indicator -->
                            <div v-if="game.has_guide" class="absolute bottom-2 left-2">
                                <span class="px-1.5 py-0.5 bg-green-500 rounded text-white text-[10px] font-bold">
                                    GUIDE
                                </span>
                            </div>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                            {{ game.title }}
                        </h3>
                        <div v-if="game.difficulty" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ game.difficulty }}/10
                        </div>
                    </router-link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import AppLayout from '../components/AppLayout.vue'
import { useAppConfig } from '../composables/useAppConfig'

const { appName } = useAppConfig()

const route = useRoute()

const loading = ref(true)
const notFound = ref(false)
const isPrivate = ref(false)
const isOwner = ref(false)
const profileUser = ref(null)
const games = ref([])
const stats = ref({ total: 0, platinumed: 0, completed: 0, in_progress: 0, backlog: 0 })
const currentStatus = ref('all')
const avatarError = ref(false)
const copied = ref(false)

const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'platinumed', label: 'Platinum' },
    { value: 'completed', label: '100%' },
    { value: 'in_progress', label: 'Playing' },
    { value: 'backlog', label: 'Backlog' },
]

const statusLabels = {
    backlog: 'Backlog',
    in_progress: 'Playing',
    completed: '100%',
    platinumed: 'Plat',
    abandoned: 'Dropped',
}

const statusColors = {
    backlog: 'bg-slate-500 text-white',
    in_progress: 'bg-sky-500 text-white',
    completed: 'bg-emerald-500 text-white',
    platinumed: 'bg-amber-500 text-black',
    abandoned: 'bg-rose-500 text-white',
}

const filteredGames = computed(() => {
    if (currentStatus.value === 'all') {
        return games.value
    }
    return games.value.filter(g => g.status === currentStatus.value)
})

function getStatusCount(status) {
    if (status === 'all') return stats.value.total
    return stats.value[status] || 0
}

// Dynamic SEO
useHead(() => {
    if (loading.value) {
        return { title: `Loading... | ${appName}` }
    }
    if (notFound.value || isPrivate.value) {
        return {
            title: `Profile | ${appName}`,
            meta: [{ name: 'robots', content: 'noindex' }],
        }
    }
    return {
        title: `${profileUser.value?.display_name}'s Games | ${appName}`,
        meta: [
            { name: 'description', content: `View ${profileUser.value?.display_name}'s trophy hunting game collection. ${stats.value.platinum} platinums, ${stats.value.total} games total.` },
        ],
    }
})

async function loadProfile() {
    loading.value = true
    notFound.value = false
    isPrivate.value = false
    avatarError.value = false

    try {
        const response = await fetch(`/api/profile/${route.params.identifier}`, {
            credentials: 'include',
        })

        if (response.status === 404) {
            notFound.value = true
            return
        }

        const data = await response.json()

        if (data.private) {
            isPrivate.value = true
            profileUser.value = data.user
            return
        }

        profileUser.value = data.user
        games.value = data.games
        stats.value = data.stats
        isOwner.value = data.is_owner
    } catch (e) {
        console.error('Failed to load profile:', e)
        notFound.value = true
    } finally {
        loading.value = false
    }
}

async function copyProfileLink() {
    try {
        await navigator.clipboard.writeText(window.location.href)
        copied.value = true
        setTimeout(() => { copied.value = false }, 2000)
    } catch (e) {
        console.error('Failed to copy:', e)
    }
}

onMounted(loadProfile)

// Reload when route changes
watch(() => route.params.identifier, loadProfile)
</script>
