<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">NP ID Management</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Link PSN NP Communication IDs to games for reliable matching
                    </p>
                </div>
                <button
                    @click="loadUnmatched"
                    :disabled="loading"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                >
                    <span v-if="loading">Loading...</span>
                    <span v-else>Refresh</span>
                </button>
            </div>

            <!-- Stats -->
            <div v-if="stats" class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    <div class="text-sm text-gray-500">Total unmatched</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-green-600">{{ stats.linked }}</div>
                    <div class="text-sm text-gray-500">Already linked</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-orange-600">{{ stats.total - stats.linked }}</div>
                    <div class="text-sm text-gray-500">Need linking</div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!loading && unmatched.length === 0" class="bg-white rounded-lg shadow p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No unmatched titles</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Load a PSN library first to see unmatched titles
                </p>
            </div>

            <!-- Unmatched List -->
            <div v-else class="space-y-4">
                <div
                    v-for="item in unmatched"
                    :key="item.np_id"
                    class="bg-white rounded-lg shadow p-4"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">{{ item.psn_title || '(Empty title)' }}</span>
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-mono rounded">
                                    {{ item.np_id }}
                                </span>
                            </div>

                            <!-- Already linked -->
                            <div v-if="item.linked_to" class="mt-2 flex items-center gap-2 text-green-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm">
                                    Linked to: <strong>{{ item.linked_to.title }}</strong>
                                    <span class="text-gray-400">(ID: {{ item.linked_to.id }})</span>
                                </span>
                                <button
                                    @click="unlinkNpId(item.linked_to.id, item.np_id)"
                                    class="text-red-500 hover:text-red-700 text-xs underline"
                                >
                                    Unlink
                                </button>
                            </div>

                            <!-- Suggestions -->
                            <div v-else-if="item.suggestions.length > 0" class="mt-3">
                                <div class="text-xs font-medium text-gray-500 mb-2">Suggestions:</div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="suggestion in item.suggestions"
                                        :key="suggestion.id"
                                        @click="linkNpId(suggestion.id, item.np_id, item)"
                                        class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 text-sm rounded hover:bg-blue-100 transition-colors"
                                    >
                                        {{ suggestion.title }}
                                        <span class="text-blue-400 text-xs">({{ suggestion.similarity }}%)</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Search -->
                            <div v-if="!item.linked_to" class="mt-3">
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="item.searchQuery"
                                        @input="searchGame(item)"
                                        type="text"
                                        placeholder="Search for game..."
                                        class="flex-1 px-3 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>
                                <div v-if="item.searchResults?.length" class="mt-2 flex flex-wrap gap-2">
                                    <button
                                        v-for="result in item.searchResults"
                                        :key="result.id"
                                        @click="linkNpId(result.id, item.np_id, item)"
                                        class="inline-flex items-center gap-1 px-2 py-1 bg-gray-50 text-gray-700 text-sm rounded hover:bg-gray-100 transition-colors border border-gray-200"
                                    >
                                        {{ result.title }}
                                        <span v-if="result.np_communication_ids?.length" class="text-gray-400 text-xs">
                                            (has {{ result.np_communication_ids.length }} NP IDs)
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from './AdminLayout.vue'

const loading = ref(false)
const unmatched = ref([])
const stats = ref(null)
let searchTimeout = null

async function loadUnmatched() {
    loading.value = true
    try {
        const response = await fetch('/api/admin/games/unmatched-psn')
        const data = await response.json()
        unmatched.value = data.unmatched.map(item => ({
            ...item,
            searchQuery: '',
            searchResults: []
        }))
        stats.value = {
            total: data.total,
            linked: data.linked
        }
    } catch (error) {
        console.error('Failed to load unmatched:', error)
    } finally {
        loading.value = false
    }
}

async function searchGame(item) {
    clearTimeout(searchTimeout)
    if (!item.searchQuery || item.searchQuery.length < 2) {
        item.searchResults = []
        return
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/api/admin/games/search-for-linking?query=${encodeURIComponent(item.searchQuery)}`)
            item.searchResults = await response.json()
        } catch (error) {
            console.error('Search failed:', error)
        }
    }, 300)
}

async function linkNpId(gameId, npId, item) {
    try {
        const response = await fetch('/api/admin/games/link-np-id', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ game_id: gameId, np_id: npId })
        })

        const data = await response.json()

        if (data.success) {
            item.linked_to = {
                id: data.game.id,
                title: data.game.title
            }
            item.searchQuery = ''
            item.searchResults = []
            if (stats.value) {
                stats.value.linked++
            }
        } else {
            alert(data.message || 'Failed to link')
        }
    } catch (error) {
        console.error('Link failed:', error)
        alert('Failed to link NP ID')
    }
}

async function unlinkNpId(gameId, npId) {
    if (!confirm('Are you sure you want to unlink this NP ID?')) return

    try {
        const response = await fetch('/api/admin/games/unlink-np-id', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ game_id: gameId, np_id: npId })
        })

        const data = await response.json()

        if (data.success) {
            // Find and update the item
            const item = unmatched.value.find(u => u.np_id === npId)
            if (item) {
                item.linked_to = null
                if (stats.value) {
                    stats.value.linked--
                }
            }
        }
    } catch (error) {
        console.error('Unlink failed:', error)
    }
}

onMounted(() => {
    loadUnmatched()
})
</script>
