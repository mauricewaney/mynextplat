<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Community Corrections</h1>
                    <p class="text-gray-600 mt-1">Review and apply user-submitted data corrections</p>
                </div>
                <!-- Stats -->
                <div class="flex items-center gap-2 text-sm">
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded font-medium">
                        {{ stats.pending }} pending
                    </span>
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded font-medium">
                        {{ stats.in_review }} in review
                    </span>
                </div>
            </div>
            <!-- Filters -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Status Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500 dark:text-gray-400">Status:</label>
                        <select
                            v-model="filters.status"
                            @change="loadCorrections"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="all">All</option>
                            <option value="pending">Pending</option>
                            <option value="in_review">In Review</option>
                            <option value="applied">Applied</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500 dark:text-gray-400">Category:</label>
                        <select
                            v-model="filters.category"
                            @change="loadCorrections"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="all">All</option>
                            <option value="trophy_data">Trophy Data</option>
                            <option value="game_info">Game Info</option>
                            <option value="guide_links">Guide Links</option>
                            <option value="images">Images</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search descriptions..."
                            class="w-full px-3 py-1.5 bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500"
                            @input="debouncedSearch"
                        />
                    </div>

                    <!-- Bulk Actions -->
                    <div v-if="selectedIds.length > 0" class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">{{ selectedIds.length }} selected</span>
                        <button
                            @click="bulkUpdate('applied')"
                            class="px-3 py-1.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700"
                        >
                            Mark Applied
                        </button>
                        <button
                            @click="bulkUpdate('rejected')"
                            class="px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"
                        >
                            Reject
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-16">
                <svg class="w-8 h-8 text-primary-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            <!-- Empty State -->
            <div v-else-if="corrections.length === 0" class="text-center py-16 text-gray-500 dark:text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-lg font-medium">No corrections found</p>
                <p class="text-sm">Try adjusting your filters</p>
            </div>

            <!-- Corrections List -->
            <div v-else class="space-y-4">
                <div
                    v-for="correction in corrections"
                    :key="correction.id"
                    class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden"
                >
                    <div class="p-4">
                        <div class="flex items-start gap-4">
                            <!-- Checkbox -->
                            <input
                                type="checkbox"
                                :checked="selectedIds.includes(correction.id)"
                                @change="toggleSelection(correction.id)"
                                class="mt-1 w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                            />

                            <!-- Game Info -->
                            <div class="shrink-0">
                                <router-link
                                    :to="`/game/${correction.game?.slug}`"
                                    target="_blank"
                                    class="block"
                                >
                                    <img
                                        v-if="correction.game?.cover_url"
                                        :src="correction.game.cover_url"
                                        :alt="correction.game.title"
                                        class="w-16 h-20 object-cover rounded"
                                    />
                                    <div v-else class="w-16 h-20 bg-gray-200 dark:bg-slate-700 rounded flex items-center justify-center">
                                        <span class="text-gray-400 text-xs">?</span>
                                    </div>
                                </router-link>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <router-link
                                        :to="`/game/${correction.game?.slug}`"
                                        target="_blank"
                                        class="font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400"
                                    >
                                        {{ correction.game?.title }}
                                    </router-link>
                                    <span :class="getCategoryClass(correction.category)" class="px-2 py-0.5 text-xs font-medium rounded">
                                        {{ getCategoryLabel(correction.category) }}
                                    </span>
                                    <span :class="getStatusClass(correction.status)" class="px-2 py-0.5 text-xs font-medium rounded">
                                        {{ getStatusLabel(correction.status) }}
                                    </span>
                                </div>

                                <p class="text-gray-600 dark:text-gray-300 text-sm mb-2">{{ correction.description }}</p>

                                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span v-if="correction.user">
                                        By {{ correction.user.name }}
                                    </span>
                                    <span v-else>
                                        Guest{{ correction.email ? ` (${correction.email})` : '' }}
                                    </span>
                                    <span>{{ formatDate(correction.created_at) }}</span>
                                    <a
                                        v-if="correction.source_url"
                                        :href="correction.source_url"
                                        target="_blank"
                                        class="text-primary-600 dark:text-primary-400 hover:underline"
                                    >
                                        View source
                                    </a>
                                </div>

                                <!-- Admin Notes -->
                                <div v-if="correction.admin_notes" class="mt-2 p-2 bg-gray-50 dark:bg-slate-700 rounded text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Notes:</span> {{ correction.admin_notes }}
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    @click="openDetail(correction)"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg"
                                    title="View details"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <router-link
                                    :to="`/admin/games?edit=${correction.game_id}`"
                                    class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg"
                                    title="Edit game"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </router-link>
                                <select
                                    :value="correction.status"
                                    @change="updateStatus(correction.id, $event.target.value)"
                                    class="px-2 py-1 text-xs bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="in_review">In Review</option>
                                    <option value="applied">Applied</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="flex justify-center gap-2 mt-6">
                    <button
                        v-for="page in pagination.last_page"
                        :key="page"
                        @click="goToPage(page)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                            page === pagination.current_page
                                ? 'bg-primary-600 text-white'
                                : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700'
                        ]"
                    >
                        {{ page }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Teleport to="body">
            <div
                v-if="detailCorrection"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="detailCorrection = null"
            >
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Correction Details</h2>
                        <button
                            @click="detailCorrection = null"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Game Info -->
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-slate-700 rounded-lg">
                            <img
                                v-if="detailCorrection.game?.cover_url"
                                :src="detailCorrection.game.cover_url"
                                :alt="detailCorrection.game.title"
                                class="w-20 h-28 object-cover rounded"
                            />
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ detailCorrection.game?.title }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ detailCorrection.game?.developer }}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <router-link
                                        :to="`/game/${detailCorrection.game?.slug}`"
                                        target="_blank"
                                        class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                                    >
                                        View game page
                                    </router-link>
                                    <router-link
                                        :to="`/admin/games?edit=${detailCorrection.game_id}`"
                                        class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                                    >
                                        Edit game
                                    </router-link>
                                </div>
                            </div>
                        </div>

                        <!-- Current Game Data (for comparison) -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Game Data</h4>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="p-2 bg-gray-50 dark:bg-slate-700 rounded">
                                    <span class="text-gray-500">Difficulty:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ detailCorrection.game?.difficulty || 'N/A' }}</span>
                                </div>
                                <div class="p-2 bg-gray-50 dark:bg-slate-700 rounded">
                                    <span class="text-gray-500">Time:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">
                                        {{ detailCorrection.game?.time_min || '?' }}-{{ detailCorrection.game?.time_max || '?' }}h
                                    </span>
                                </div>
                                <div class="p-2 bg-gray-50 dark:bg-slate-700 rounded">
                                    <span class="text-gray-500">Playthroughs:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ detailCorrection.game?.playthroughs_required || 'N/A' }}</span>
                                </div>
                                <div class="p-2 bg-gray-50 dark:bg-slate-700 rounded">
                                    <span class="text-gray-500">Online:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ detailCorrection.game?.has_online_trophies ? 'Yes' : 'No' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Reported Issue -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reported Issue</h4>
                            <div class="flex items-center gap-2 mb-2">
                                <span :class="getCategoryClass(detailCorrection.category)" class="px-2 py-0.5 text-xs font-medium rounded">
                                    {{ getCategoryLabel(detailCorrection.category) }}
                                </span>
                            </div>
                            <p class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-gray-900 dark:text-white">
                                {{ detailCorrection.description }}
                            </p>
                            <a
                                v-if="detailCorrection.source_url"
                                :href="detailCorrection.source_url"
                                target="_blank"
                                class="inline-flex items-center gap-1 mt-2 text-sm text-primary-600 dark:text-primary-400 hover:underline"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                View source
                            </a>
                        </div>

                        <!-- Admin Notes -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Admin Notes</h4>
                            <textarea
                                v-model="detailNotes"
                                rows="3"
                                placeholder="Add notes about this correction..."
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 resize-none"
                            ></textarea>
                        </div>

                        <!-- Status & Actions -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-700">
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-500">Status:</label>
                                <select
                                    v-model="detailStatus"
                                    class="px-3 py-1.5 bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="in_review">In Review</option>
                                    <option value="applied">Applied</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="deleteCorrection(detailCorrection.id)"
                                    class="px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm font-medium"
                                >
                                    Delete
                                </button>
                                <button
                                    @click="saveDetail"
                                    :disabled="detailSaving"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
                                >
                                    {{ detailSaving ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import AdminLayout from './AdminLayout.vue'

const corrections = ref([])
const loading = ref(true)
const stats = ref({ pending: 0, in_review: 0, applied: 0, rejected: 0 })
const pagination = ref({ current_page: 1, last_page: 1 })
const selectedIds = ref([])

const filters = reactive({
    status: 'pending',
    category: 'all',
    search: '',
})

// Detail modal
const detailCorrection = ref(null)
const detailNotes = ref('')
const detailStatus = ref('')
const detailSaving = ref(false)

const categoryLabels = {
    trophy_data: 'Trophy Data',
    game_info: 'Game Info',
    guide_links: 'Guide Links',
    images: 'Images',
    other: 'Other',
}

const statusLabels = {
    pending: 'Pending',
    in_review: 'In Review',
    applied: 'Applied',
    rejected: 'Rejected',
}

function getCategoryLabel(category) {
    return categoryLabels[category] || category
}

function getStatusLabel(status) {
    return statusLabels[status] || status
}

function getCategoryClass(category) {
    const classes = {
        trophy_data: 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300',
        game_info: 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
        guide_links: 'bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300',
        images: 'bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300',
        other: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    }
    return classes[category] || classes.other
}

function getStatusClass(status) {
    const classes = {
        pending: 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300',
        in_review: 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
        applied: 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300',
        rejected: 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300',
    }
    return classes[status] || classes.pending
}

function formatDate(dateString) {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

async function loadStats() {
    try {
        const response = await fetch('/api/admin/corrections/stats', { credentials: 'include' })
        stats.value = await response.json()
    } catch (e) {
        console.error('Failed to load stats:', e)
    }
}

async function loadCorrections(page = 1) {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page,
            status: filters.status,
            category: filters.category,
        })
        if (filters.search) {
            params.append('search', filters.search)
        }

        const response = await fetch(`/api/admin/corrections?${params}`, { credentials: 'include' })
        const data = await response.json()

        corrections.value = data.data
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
        }
    } catch (e) {
        console.error('Failed to load corrections:', e)
    } finally {
        loading.value = false
    }
}

let searchDebounce = null
function debouncedSearch() {
    clearTimeout(searchDebounce)
    searchDebounce = setTimeout(() => loadCorrections(), 300)
}

function goToPage(page) {
    loadCorrections(page)
}

function toggleSelection(id) {
    const index = selectedIds.value.indexOf(id)
    if (index > -1) {
        selectedIds.value.splice(index, 1)
    } else {
        selectedIds.value.push(id)
    }
}

async function updateStatus(id, status) {
    try {
        await fetch(`/api/admin/corrections/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ status }),
        })
        loadCorrections(pagination.value.current_page)
        loadStats()
    } catch (e) {
        console.error('Failed to update status:', e)
    }
}

async function bulkUpdate(status) {
    if (selectedIds.value.length === 0) return

    try {
        await fetch('/api/admin/corrections/bulk-update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ ids: selectedIds.value, status }),
        })
        selectedIds.value = []
        loadCorrections(pagination.value.current_page)
        loadStats()
    } catch (e) {
        console.error('Failed to bulk update:', e)
    }
}

async function openDetail(correction) {
    // Fetch full details
    try {
        const response = await fetch(`/api/admin/corrections/${correction.id}`, { credentials: 'include' })
        detailCorrection.value = await response.json()
        detailNotes.value = detailCorrection.value.admin_notes || ''
        detailStatus.value = detailCorrection.value.status
    } catch (e) {
        console.error('Failed to load correction details:', e)
    }
}

async function saveDetail() {
    if (!detailCorrection.value) return

    detailSaving.value = true
    try {
        await fetch(`/api/admin/corrections/${detailCorrection.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                status: detailStatus.value,
                admin_notes: detailNotes.value,
            }),
        })
        detailCorrection.value = null
        loadCorrections(pagination.value.current_page)
        loadStats()
    } catch (e) {
        console.error('Failed to save:', e)
    } finally {
        detailSaving.value = false
    }
}

async function deleteCorrection(id) {
    if (!confirm('Are you sure you want to delete this correction?')) return

    try {
        await fetch(`/api/admin/corrections/${id}`, {
            method: 'DELETE',
            credentials: 'include',
        })
        detailCorrection.value = null
        loadCorrections(pagination.value.current_page)
        loadStats()
    } catch (e) {
        console.error('Failed to delete:', e)
    }
}

onMounted(() => {
    loadStats()
    loadCorrections()
})
</script>
