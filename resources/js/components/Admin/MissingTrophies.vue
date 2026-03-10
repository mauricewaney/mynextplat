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

            <!-- Collect NP IDs -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Collect NP IDs from PSN User</h3>
                <div class="flex gap-2">
                    <input
                        v-model="collectUsername"
                        @keyup.enter="collectFromUser"
                        type="text"
                        placeholder="Enter PSN username..."
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        :disabled="collecting"
                    />
                    <button
                        @click="collectFromUser"
                        :disabled="collecting || !collectUsername.trim()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors whitespace-nowrap"
                    >
                        <span v-if="collecting">Collecting...</span>
                        <span v-else>Collect NP IDs</span>
                    </button>
                </div>
                <p v-if="collectResult" class="mt-2 text-sm" :class="collectResult.success ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ collectResult.message }}
                </p>
                <div v-if="collectResult && !collectResult.success && collectResult.suggestions?.length" class="mt-1 flex items-center gap-1 flex-wrap">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Try:</span>
                    <button
                        v-for="s in collectResult.suggestions"
                        :key="s"
                        @click="collectUsername = s; collectFromUser()"
                        class="text-xs px-2 py-0.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded hover:bg-primary-100 dark:hover:bg-primary-900/50 transition-colors"
                    >{{ s }}</button>
                </div>
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
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ game.title }}</span>
                                        <button
                                            @click="copyTitle(game)"
                                            class="p-1 rounded text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
                                            title="Copy title"
                                        >
                                            <svg v-if="copiedId === game.id" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                        <a
                                            href="https://psnprofiles.com/games"
                                            target="_blank"
                                            class="p-1 rounded text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
                                            title="Search on PSNProfiles"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
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
const collectUsername = ref('')
const collecting = ref(false)
const collectResult = ref(null)

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

async function collectFromUser() {
    if (!collectUsername.value.trim()) return

    collecting.value = true
    collectResult.value = null

    try {
        const response = await fetch(`/api/admin/psn/collect/${encodeURIComponent(collectUsername.value.trim())}`)

        if (!response.ok) {
            let errorMsg = `Server error (${response.status})`
            try {
                const errData = await response.json()
                errorMsg = errData.message || errorMsg
            } catch {
                if (response.status === 504) {
                    errorMsg = 'Gateway timeout — the PSN library is too large and the request timed out. Try a user with fewer games.'
                } else if (response.status === 500) {
                    errorMsg = 'Server error — the PSN API may have timed out. Try again.'
                }
            }
            collectResult.value = { success: false, message: errorMsg }
            return
        }

        const data = await response.json()

        let suggestions = []
        if (!data.success && data.message) {
            const match = data.message.match(/Did you mean: (.+)\?/)
            if (match) {
                suggestions = match[1].split(', ').map(s => s.trim())
            }
        }

        collectResult.value = {
            success: data.success,
            message: data.success
                ? `Collected ${data.new_titles} new titles from ${data.username} (${data.existing_titles} already existed, ${data.auto_matched || 0} auto-matched)`
                : data.message,
            suggestions,
        }

        if (data.success) {
            collectUsername.value = ''
        }
    } catch (error) {
        collectResult.value = {
            success: false,
            message: 'Network error — could not reach server. Check your connection and try again.'
        }
    } finally {
        collecting.value = false
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
