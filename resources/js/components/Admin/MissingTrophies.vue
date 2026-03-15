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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Trophy Breakdown</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">IGDB ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <template v-for="game in games" :key="game.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
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
                                        <button
                                            @click="openEditModal(game)"
                                            class="p-1 rounded text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors flex-shrink-0"
                                            title="Edit game"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
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
                                        <button
                                            @click="openMerge(game)"
                                            class="p-1 rounded transition-colors flex-shrink-0"
                                            :class="mergeGameId === game.id
                                                ? 'text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/30'
                                                : 'text-gray-400 hover:text-orange-600 dark:hover:text-orange-400 hover:bg-gray-100 dark:hover:bg-slate-700'"
                                            title="Merge with another game"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
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
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <label class="inline-flex items-center gap-0.5">
                                        <span class="text-[10px] text-blue-300 font-bold">P</span>
                                        <input
                                            type="checkbox"
                                            :checked="game._platinum"
                                            @change="game._platinum = $event.target.checked"
                                            class="w-3.5 h-3.5 rounded border-gray-300 text-blue-400 focus:ring-blue-300"
                                        />
                                    </label>
                                    <label class="inline-flex items-center gap-0.5">
                                        <span class="text-[10px] text-yellow-500 font-bold">G</span>
                                        <input
                                            v-model.number="game._gold"
                                            type="number"
                                            min="0"
                                            class="w-10 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded focus:ring-1 focus:ring-yellow-500"
                                            placeholder="0"
                                        />
                                    </label>
                                    <label class="inline-flex items-center gap-0.5">
                                        <span class="text-[10px] text-gray-400 font-bold">S</span>
                                        <input
                                            v-model.number="game._silver"
                                            type="number"
                                            min="0"
                                            class="w-10 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded focus:ring-1 focus:ring-gray-400"
                                            placeholder="0"
                                        />
                                    </label>
                                    <label class="inline-flex items-center gap-0.5">
                                        <span class="text-[10px] text-amber-700 dark:text-amber-500 font-bold">B</span>
                                        <input
                                            v-model.number="game._bronze"
                                            type="number"
                                            min="0"
                                            class="w-10 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded focus:ring-1 focus:ring-amber-500"
                                            placeholder="0"
                                        />
                                    </label>
                                    <button
                                        @click="saveTrophies(game)"
                                        :disabled="game._saving"
                                        class="px-2 py-0.5 text-xs font-medium rounded bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 transition-colors"
                                    >
                                        {{ game._saving ? '...' : 'Save' }}
                                    </button>
                                    <svg v-if="game._saved" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ game.igdb_id || '—' }}
                            </td>
                        </tr>

                        <!-- Inline Merge Panel -->
                        <tr v-if="mergeGameId === game.id" class="bg-orange-50/50 dark:bg-orange-900/10">
                            <td colspan="4" class="px-4 py-3">
                                <!-- Success flash -->
                                <div v-if="mergeSuccess" class="mb-3 px-3 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm rounded-lg flex items-center gap-2">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ mergeSuccess }}
                                </div>

                                <!-- Confirmation bar (when target selected) -->
                                <div v-if="mergeTarget" class="flex flex-wrap items-center gap-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-orange-200 dark:border-orange-800/50">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            Merge
                                            <span class="font-semibold text-red-600 dark:text-red-400">{{ mergeDuplicate.title }}</span>
                                            <span class="text-gray-400 mx-1">(ID {{ mergeDuplicate.id }})</span>
                                            into
                                            <span class="font-semibold text-green-600 dark:text-green-400">{{ mergeKeeper.title }}</span>
                                            <span class="text-gray-400 mx-1">(ID {{ mergeKeeper.id }})</span>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            The keeper inherits guides, trophies, and stats from the duplicate if missing.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="mergeSwapped = !mergeSwapped"
                                            class="px-2 py-1 text-xs font-medium rounded bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors"
                                            title="Swap keeper and duplicate"
                                            :disabled="merging"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                            </svg>
                                        </button>
                                        <button
                                            @click="executeMerge(game)"
                                            :disabled="merging"
                                            class="px-3 py-1.5 text-xs font-medium rounded bg-orange-600 text-white hover:bg-orange-700 disabled:opacity-50 transition-colors"
                                        >
                                            <span v-if="merging">Merging...</span>
                                            <span v-else>Merge</span>
                                        </button>
                                        <button
                                            @click="mergeTarget = null; mergeSwapped = false"
                                            :disabled="merging"
                                            class="px-3 py-1.5 text-xs font-medium rounded bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-slate-600 disabled:opacity-50 transition-colors"
                                        >Cancel</button>
                                    </div>
                                </div>

                                <!-- Search input + results (when no target selected) -->
                                <div v-else>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Merge into:</span>
                                        <div class="flex-1 relative">
                                            <input
                                                ref="mergeSearchInput"
                                                v-model="mergeQuery"
                                                @input="debouncedSearchMerge"
                                                type="text"
                                                placeholder="Search for the game to keep..."
                                                class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                            />
                                            <svg v-if="mergeSearching" class="absolute right-2.5 top-2 w-4 h-4 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                            </svg>
                                        </div>
                                        <button
                                            @click="closeMerge"
                                            class="p-1.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                                            title="Close"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Search results -->
                                    <div v-if="mergeResults.length > 0" class="space-y-1">
                                        <button
                                            v-for="result in mergeResults"
                                            :key="result.id"
                                            @click="selectMergeTarget(result)"
                                            class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-white dark:hover:bg-slate-800 transition-colors text-left border border-transparent hover:border-gray-200 dark:hover:border-slate-600"
                                        >
                                            <img
                                                v-if="result.cover_url"
                                                :src="result.cover_url"
                                                :alt="result.title"
                                                class="w-8 h-11 object-cover rounded flex-shrink-0"
                                            />
                                            <div v-else class="w-8 h-11 bg-gray-200 dark:bg-slate-600 rounded flex-shrink-0"></div>
                                            <div class="flex-1 min-w-0">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate block">{{ result.title }}</span>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="text-xs text-gray-400">ID {{ result.id }}</span>
                                                    <span v-if="result.bronze_count || result.silver_count || result.gold_count || result.platinum_count" class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ (result.bronze_count || 0) + (result.silver_count || 0) + (result.gold_count || 0) + (result.platinum_count || 0) }} trophies
                                                    </span>
                                                    <span v-if="result.psnprofiles_url" class="px-1 py-0.5 text-[10px] font-medium rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">PSNP</span>
                                                    <span v-if="result.playstationtrophies_url" class="px-1 py-0.5 text-[10px] font-medium rounded bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">PST</span>
                                                    <span v-if="result.powerpyx_url" class="px-1 py-0.5 text-[10px] font-medium rounded bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">PPX</span>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                    <p v-else-if="mergeQuery.length >= 2 && !mergeSearching" class="text-xs text-gray-400 dark:text-gray-500 py-2">
                                        No results found.
                                    </p>
                                </div>
                            </td>
                        </tr>
                        </template>
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

        <GameFormModal
            :show="showGameModal"
            :game="editingGame"
            :genres="formData.genres"
            :tags="formData.tags"
            :platforms="formData.platforms"
            @close="closeGameModal"
            @saved="handleGameSaved"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import AdminLayout from './AdminLayout.vue'
import GameFormModal from './GameFormModal.vue'

const games = ref([])
const loading = ref(true)
const hasGuideFilter = ref(true)
const copiedId = ref(null)
const pagination = ref(null)
const guidesOnlyCount = ref(null)
const collectUsername = ref('')
const collecting = ref(false)
const collectResult = ref(null)

// Merge state
const mergeGameId = ref(null)
const mergeQuery = ref('')
const mergeResults = ref([])
const mergeSearching = ref(false)
const mergeTarget = ref(null)
const mergeSwapped = ref(false)
const merging = ref(false)
const mergeSuccess = ref(null)
const mergeSearchInput = ref(null)
let mergeDebounceTimer = null

// Edit modal state
const editingGame = ref(null)
const showGameModal = computed(() => editingGame.value !== null)
const formData = reactive({ genres: [], tags: [], platforms: [] })

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
            games.value = data.data.map(g => ({
                ...g,
                _platinum: g.has_platinum || false,
                _gold: g.gold_count || null,
                _silver: g.silver_count || null,
                _bronze: g.bronze_count || null,
                _saving: false,
                _saved: false,
            }))
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

async function saveTrophies(game) {
    game._saving = true
    game._saved = false
    try {
        const response = await fetch(`/api/admin/games/${game.id}/trophies`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                has_platinum: game._platinum,
                gold_count: game._gold || 0,
                silver_count: game._silver || 0,
                bronze_count: game._bronze || 0,
            })
        })

        if (response.ok) {
            game._saved = true
            setTimeout(() => {
                // Remove from list since it now has trophy data
                games.value = games.value.filter(g => g.id !== game.id)
            }, 800)
        } else {
            const err = await response.json()
            alert(err.message || 'Failed to save')
        }
    } catch (error) {
        alert('Failed to save trophy data')
    } finally {
        game._saving = false
    }
}

function copyTitle(game) {
    navigator.clipboard.writeText(game.title)
    copiedId.value = game.id
    setTimeout(() => {
        if (copiedId.value === game.id) copiedId.value = null
    }, 1500)
}

// Merge helpers
const mergeKeeper = computed(() => {
    if (!mergeTarget.value) return null
    const currentGame = games.value.find(g => g.id === mergeGameId.value)
    return mergeSwapped.value ? currentGame : mergeTarget.value
})

const mergeDuplicate = computed(() => {
    if (!mergeTarget.value) return null
    const currentGame = games.value.find(g => g.id === mergeGameId.value)
    return mergeSwapped.value ? mergeTarget.value : currentGame
})

function openMerge(game) {
    if (mergeGameId.value === game.id) {
        closeMerge()
        return
    }
    mergeGameId.value = game.id
    mergeQuery.value = ''
    mergeResults.value = []
    mergeTarget.value = null
    mergeSwapped.value = false
    mergeSuccess.value = null
    nextTick(() => {
        mergeSearchInput.value?.focus()
    })
}

function closeMerge() {
    mergeGameId.value = null
    mergeQuery.value = ''
    mergeResults.value = []
    mergeTarget.value = null
    mergeSwapped.value = false
    mergeSuccess.value = null
}

function debouncedSearchMerge() {
    clearTimeout(mergeDebounceTimer)
    if (mergeQuery.value.length < 2) {
        mergeResults.value = []
        return
    }
    mergeDebounceTimer = setTimeout(searchMerge, 300)
}

async function searchMerge() {
    if (mergeQuery.value.length < 2) return
    mergeSearching.value = true
    try {
        const params = new URLSearchParams({ query: mergeQuery.value })
        params.append('exclude_ids[]', mergeGameId.value)
        const response = await fetch(`/api/admin/games/search-for-merge?${params}`)
        if (!response.ok) throw new Error('search failed')
        mergeResults.value = await response.json()
    } catch (error) {
        console.error('Error searching for merge:', error)
    } finally {
        mergeSearching.value = false
    }
}

function selectMergeTarget(game) {
    mergeTarget.value = game
    mergeSwapped.value = false
}

async function executeMerge(currentGame) {
    if (!mergeTarget.value || merging.value) return
    merging.value = true
    try {
        const response = await fetch('/api/admin/games/merge', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                primary_id: mergeKeeper.value.id,
                duplicate_id: mergeDuplicate.value.id,
            }),
        })
        if (!response.ok) {
            const err = await response.json()
            throw new Error(err.message || 'Merge failed')
        }
        const data = await response.json()
        mergeSuccess.value = data.message || `Merged successfully`
        mergeTarget.value = null
        mergeSwapped.value = false
        // Remove the current game from the list (it was the duplicate or got merged)
        setTimeout(() => {
            games.value = games.value.filter(g => g.id !== currentGame.id)
            closeMerge()
        }, 1500)
    } catch (error) {
        console.error('Merge failed:', error)
        alert('Merge failed: ' + error.message)
    } finally {
        merging.value = false
    }
}

// Edit modal
async function fetchFormData() {
    try {
        const response = await fetch('/api/admin/games/form-data')
        if (!response.ok) throw new Error('Failed to fetch form data')
        const data = await response.json()
        formData.genres = Array.isArray(data.genres) ? data.genres : []
        formData.tags = Array.isArray(data.tags) ? data.tags : []
        formData.platforms = Array.isArray(data.platforms) ? data.platforms : []
    } catch (error) {
        console.error('Error fetching form data:', error)
    }
}

async function openEditModal(game) {
    try {
        const response = await fetch(`/api/admin/games/${game.id}`)
        if (!response.ok) throw new Error('Failed to load game')
        editingGame.value = await response.json()
    } catch (error) {
        console.error('Error loading game for edit:', error)
    }
}

function closeGameModal() {
    editingGame.value = null
}

function handleGameSaved(savedGame) {
    // Update the game in our list if it still belongs (still missing trophies)
    const index = games.value.findIndex(g => g.id === savedGame.id)
    if (index !== -1) {
        const hasTrophies = (savedGame.bronze_count || 0) + (savedGame.silver_count || 0) + (savedGame.gold_count || 0) + (savedGame.platinum_count || 0) > 0
        if (hasTrophies) {
            games.value.splice(index, 1)
        } else {
            games.value[index] = { ...games.value[index], ...savedGame }
        }
    }
    editingGame.value = null
}

onMounted(() => {
    fetchGames()
    fetchGuideCount()
    fetchFormData()
})
</script>
