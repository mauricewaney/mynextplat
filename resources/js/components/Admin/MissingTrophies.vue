<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Missing Trophy Data</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1" v-if="pagination">
                        {{ pagination.total }} games missing trophy data
                        <span v-if="guidesOnlyCount !== null"> ({{ guidesOnlyCount }} with guides)</span>
                    </p>
                </div>

                <label class="flex items-center gap-2 cursor-pointer bg-white dark:bg-slate-800 rounded-lg shadow px-4 py-2">
                    <input
                        type="checkbox"
                        v-model="hasGuideFilter"
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        @change="fetchGames(1)"
                    />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Has guide only</span>
                </label>
            </div>

            <!-- Games List -->
            <div class="bg-white dark:bg-slate-800 shadow rounded-lg overflow-hidden">
                <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    Loading...
                </div>

                <div v-else-if="games.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    No games missing trophy data.
                </div>

                <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Game</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Guides</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">IGDB ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <tr v-for="game in games" :key="game.id" class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="game.cover_url"
                                        :src="game.cover_url"
                                        :alt="game.title"
                                        class="w-10 h-14 object-cover rounded flex-shrink-0"
                                    />
                                    <div v-else class="w-10 h-14 bg-gray-200 dark:bg-slate-600 rounded flex-shrink-0 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ game.title }}</span>
                                        <button
                                            @click="copyTitle(game)"
                                            class="p-1 rounded text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
                                            :title="'Copy title'"
                                        >
                                            <svg v-if="copiedId === game.id" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a v-if="game.psnprofiles_url" :href="game.psnprofiles_url" target="_blank"
                                        class="px-1.5 py-0.5 text-xs font-medium rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/60"
                                    >PSNP</a>
                                    <a v-if="game.playstationtrophies_url" :href="game.playstationtrophies_url" target="_blank"
                                        class="px-1.5 py-0.5 text-xs font-medium rounded bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-900/60"
                                    >PST</a>
                                    <a v-if="game.powerpyx_url" :href="game.powerpyx_url" target="_blank"
                                        class="px-1.5 py-0.5 text-xs font-medium rounded bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/60"
                                    >PPX</a>
                                    <span v-if="!game.psnprofiles_url && !game.playstationtrophies_url && !game.powerpyx_url"
                                        class="text-xs text-gray-400 dark:text-gray-500"
                                    >None</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ game.igdb_id || '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="pagination && pagination.last_page > 1" class="px-4 py-3 bg-gray-50 dark:bg-slate-700/50 border-t border-gray-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Showing {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }}
                    </div>
                    <div class="flex gap-1">
                        <button
                            v-for="page in paginationPages"
                            :key="page"
                            @click="fetchGames(page)"
                            :class="[
                                'px-3 py-1 text-sm rounded',
                                page === pagination.current_page
                                    ? 'bg-primary-600 text-white'
                                    : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-600'
                            ]"
                        >
                            {{ page }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from './AdminLayout.vue'

const games = ref([])
const loading = ref(true)
const hasGuideFilter = ref(true)
const copiedId = ref(null)
const pagination = ref(null)
const guidesOnlyCount = ref(null)

const paginationPages = computed(() => {
    if (!pagination.value) return []
    const current = pagination.value.current_page
    const last = pagination.value.last_page
    const pages = []
    const start = Math.max(1, current - 2)
    const end = Math.min(last, current + 2)
    for (let i = start; i <= end; i++) {
        pages.push(i)
    }
    return pages
})

async function fetchGames(page = 1) {
    loading.value = true
    try {
        const params = new URLSearchParams({ page, per_page: 50 })
        if (hasGuideFilter.value) params.set('has_guide', '1')

        const response = await fetch(`/api/admin/games/missing-trophies?${params}`, { credentials: 'include' })
        if (response.ok) {
            const data = await response.json()
            games.value = data.data
            pagination.value = {
                current_page: data.current_page,
                last_page: data.last_page,
                total: data.total,
                from: data.from,
                to: data.to,
            }
        }
    } catch (e) {
        console.error('Failed to fetch missing trophies:', e)
    } finally {
        loading.value = false
    }
}

async function fetchGuideCount() {
    try {
        const response = await fetch('/api/admin/games/stats', { credentials: 'include' })
        if (response.ok) {
            const stats = await response.json()
            guidesOnlyCount.value = stats.guide_missing_trophies
        }
    } catch (e) {
        // ignore
    }
}

function copyTitle(game) {
    navigator.clipboard.writeText(game.title)
    copiedId.value = game.id
    setTimeout(() => {
        if (copiedId.value === game.id) copiedId.value = null
    }, 1500)
}

onMounted(() => {
    fetchGames()
    fetchGuideCount()
})
</script>
