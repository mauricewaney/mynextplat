<template>
    <AppLayout title="Public Profiles">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Public Profiles</h1>
                <p class="text-gray-500 dark:text-gray-400">
                    Browse trophy hunters and their game collections
                </p>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="n in 6"
                    :key="n"
                    class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-5 animate-pulse"
                >
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-gray-200 dark:bg-slate-700 rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-5 bg-gray-200 dark:bg-slate-700 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded w-1/3"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="profiles.length === 0" class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No public profiles yet</h3>
                <p class="text-gray-500 dark:text-gray-400">
                    Be the first to share your collection!
                </p>
            </div>

            <!-- Profiles Grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <router-link
                    v-for="profile in profiles"
                    :key="profile.id"
                    :to="`/u/${profile.profile_slug || profile.id}`"
                    class="group bg-white dark:bg-slate-800 rounded-xl shadow-sm p-5 hover:shadow-md dark:hover:bg-slate-700/50 transition-all"
                >
                    <div class="flex items-center gap-4">
                        <!-- Avatar -->
                        <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center overflow-hidden shrink-0">
                            <img
                                v-if="profile.avatar"
                                :src="profile.avatar"
                                :alt="profile.display_name"
                                class="w-14 h-14 rounded-full object-cover"
                                @error="$event.target.style.display='none'"
                            />
                            <span v-else class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                {{ profile.display_name?.charAt(0) || '?' }}
                            </span>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors truncate">
                                {{ profile.display_name }}
                            </h3>
                            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400 mt-1">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    {{ profile.games_count }} games
                                </span>
                                <span v-if="profile.platinum_count > 0" class="flex items-center gap-1 text-amber-500">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                                    </svg>
                                    {{ profile.platinum_count }}
                                </span>
                            </div>
                        </div>

                        <!-- Arrow -->
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </router-link>
            </div>

            <!-- Load More -->
            <div v-if="hasMore && profiles.length > 0" class="mt-8 text-center">
                <button
                    @click="loadMore"
                    :disabled="loadingMore"
                    class="px-6 py-2.5 bg-white dark:bg-slate-800 text-primary-600 dark:text-primary-400 font-medium rounded-lg shadow-sm hover:shadow-md transition-all disabled:opacity-50"
                >
                    <span v-if="loadingMore" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Loading...
                    </span>
                    <span v-else>Load more</span>
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useHead } from '@vueuse/head'
import AppLayout from '../components/AppLayout.vue'
import { useAppConfig } from '../composables/useAppConfig'

const { appName } = useAppConfig()

useHead({
    title: `Public Profiles | ${appName}`,
    meta: [
        { name: 'description', content: 'Browse public trophy hunting profiles and game collections. See what other PlayStation trophy hunters are playing and their platinum achievements.' },
        { property: 'og:title', content: `Public Profiles | ${appName}` },
        { property: 'og:description', content: 'Browse public trophy hunting profiles and game collections.' },
    ],
})

const profiles = ref([])
const loading = ref(true)
const loadingMore = ref(false)
const currentPage = ref(1)
const hasMore = ref(false)

async function loadProfiles(page = 1) {
    try {
        const response = await fetch(`/api/profiles?page=${page}`)
        const data = await response.json()

        if (page === 1) {
            profiles.value = data.data
        } else {
            profiles.value.push(...data.data)
        }

        currentPage.value = data.current_page
        hasMore.value = data.current_page < data.last_page
    } catch (e) {
        console.error('Failed to load profiles:', e)
    } finally {
        loading.value = false
        loadingMore.value = false
    }
}

function loadMore() {
    loadingMore.value = true
    loadProfiles(currentPage.value + 1)
}

onMounted(() => loadProfiles())
</script>
