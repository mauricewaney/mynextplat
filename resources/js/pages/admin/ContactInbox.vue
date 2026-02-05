<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Contact Inbox</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">View and manage messages from the contact form</p>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300 rounded font-medium">
                        {{ pendingCount }} pending
                    </span>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Status Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500 dark:text-gray-400">Status:</label>
                        <select
                            v-model="filters.status"
                            @change="loadMessages"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="all">All</option>
                            <option value="pending">Pending</option>
                            <option value="read">Read</option>
                            <option value="replied">Replied</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500 dark:text-gray-400">Category:</label>
                        <select
                            v-model="filters.category"
                            @change="loadMessages"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="all">All</option>
                            <option value="general">General</option>
                            <option value="bug_report">Bug Report</option>
                            <option value="suggestion">Suggestion</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search messages..."
                            class="w-full px-3 py-1.5 bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500"
                            @input="debouncedSearch"
                        />
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
            <div v-else-if="messages.length === 0" class="text-center py-16 text-gray-500 dark:text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <p class="text-lg font-medium">No messages found</p>
                <p class="text-sm">Try adjusting your filters</p>
            </div>

            <!-- Messages List -->
            <div v-else class="space-y-3">
                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    @click="openDetail(msg)"
                    :class="[
                        'bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 cursor-pointer hover:shadow-md transition-shadow',
                        msg.status === 'pending' ? 'border-l-4 border-l-yellow-400' : ''
                    ]"
                >
                    <div class="flex items-start gap-4">
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="font-medium text-gray-900 dark:text-white">{{ msg.name }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">&lt;{{ msg.email }}&gt;</span>
                                <span :class="getCategoryClass(msg.category)" class="px-2 py-0.5 text-xs font-medium rounded">
                                    {{ getCategoryLabel(msg.category) }}
                                </span>
                                <span :class="getStatusClass(msg.status)" class="px-2 py-0.5 text-xs font-medium rounded">
                                    {{ getStatusLabel(msg.status) }}
                                </span>
                            </div>

                            <h3 class="font-medium text-gray-800 dark:text-gray-200 text-sm mb-1">{{ msg.subject }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm line-clamp-2">{{ msg.message }}</p>

                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-400 dark:text-gray-500">
                                <span v-if="msg.user">Registered user: {{ msg.user.name }}</span>
                                <span v-else>Guest</span>
                                <span>{{ formatDate(msg.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="flex items-center gap-1 shrink-0" @click.stop>
                            <select
                                :value="msg.status"
                                @change="updateStatus(msg.id, $event.target.value)"
                                class="px-2 py-1 text-xs bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="pending">Pending</option>
                                <option value="read">Read</option>
                                <option value="replied">Replied</option>
                                <option value="closed">Closed</option>
                            </select>
                            <button
                                @click="deleteMessage(msg.id)"
                                class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                                title="Delete"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
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
                v-if="detailMessage"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="detailMessage = null"
            >
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Message Details</h2>
                        <button
                            @click="detailMessage = null"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Sender Info -->
                        <div class="p-4 bg-gray-50 dark:bg-slate-700 rounded-lg">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">From:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ detailMessage.name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Email:</span>
                                    <a :href="`mailto:${detailMessage.email}`" class="ml-2 text-primary-600 dark:text-primary-400 hover:underline">{{ detailMessage.email }}</a>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Category:</span>
                                    <span :class="getCategoryClass(detailMessage.category)" class="ml-2 px-2 py-0.5 text-xs font-medium rounded">
                                        {{ getCategoryLabel(detailMessage.category) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Date:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ formatDate(detailMessage.created_at) }}</span>
                                </div>
                                <div v-if="detailMessage.user">
                                    <span class="text-gray-500 dark:text-gray-400">Account:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ detailMessage.user.name }} ({{ detailMessage.user.email }})</span>
                                </div>
                                <div v-if="detailMessage.ip_address">
                                    <span class="text-gray-500 dark:text-gray-400">IP:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white font-mono text-xs">{{ detailMessage.ip_address }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Subject & Message -->
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">{{ detailMessage.subject }}</h3>
                            <div class="p-4 bg-gray-50 dark:bg-slate-700 rounded-lg">
                                <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ detailMessage.message }}</p>
                            </div>
                        </div>

                        <!-- Status & Actions -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-700">
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-500 dark:text-gray-400">Status:</label>
                                <select
                                    v-model="detailStatus"
                                    class="px-3 py-1.5 bg-gray-100 dark:bg-slate-700 border-0 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="read">Read</option>
                                    <option value="replied">Replied</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <a
                                    :href="`mailto:${detailMessage.email}?subject=Re: ${detailMessage.subject}`"
                                    class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors"
                                >
                                    Reply via Email
                                </a>
                                <button
                                    @click="deleteMessage(detailMessage.id); detailMessage = null"
                                    class="px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm font-medium"
                                >
                                    Delete
                                </button>
                                <button
                                    @click="saveDetailStatus"
                                    :disabled="detailSaving"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
                                >
                                    {{ detailSaving ? 'Saving...' : 'Update Status' }}
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
import AdminLayout from '../../components/Admin/AdminLayout.vue'

const messages = ref([])
const loading = ref(true)
const pendingCount = ref(0)
const pagination = ref({ current_page: 1, last_page: 1 })

const filters = reactive({
    status: 'all',
    category: 'all',
    search: '',
})

// Detail modal
const detailMessage = ref(null)
const detailStatus = ref('')
const detailSaving = ref(false)

const categoryLabels = {
    general: 'General',
    bug_report: 'Bug Report',
    suggestion: 'Suggestion',
    other: 'Other',
}

const statusLabels = {
    pending: 'Pending',
    read: 'Read',
    replied: 'Replied',
    closed: 'Closed',
}

function getCategoryLabel(category) {
    return categoryLabels[category] || category
}

function getStatusLabel(status) {
    return statusLabels[status] || status
}

function getCategoryClass(category) {
    const classes = {
        general: 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
        bug_report: 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300',
        suggestion: 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300',
        other: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    }
    return classes[category] || classes.other
}

function getStatusClass(status) {
    const classes = {
        pending: 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300',
        read: 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
        replied: 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300',
        closed: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
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

async function loadMessages(page = 1) {
    loading.value = true
    try {
        const params = new URLSearchParams({ page })
        if (filters.status !== 'all') params.append('status', filters.status)
        if (filters.category !== 'all') params.append('category', filters.category)
        if (filters.search) params.append('search', filters.search)

        const response = await fetch(`/api/admin/contact?${params}`, { credentials: 'include' })
        const data = await response.json()

        messages.value = data.data
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
        }

        // Count pending from current data or separately
        loadPendingCount()
    } catch (e) {
        console.error('Failed to load messages:', e)
    } finally {
        loading.value = false
    }
}

async function loadPendingCount() {
    try {
        const response = await fetch('/api/admin/contact?status=pending&per_page=1', { credentials: 'include' })
        const data = await response.json()
        pendingCount.value = data.total
    } catch (e) {
        // Silently fail
    }
}

let searchDebounce = null
function debouncedSearch() {
    clearTimeout(searchDebounce)
    searchDebounce = setTimeout(() => loadMessages(), 300)
}

function goToPage(page) {
    loadMessages(page)
}

async function updateStatus(id, status) {
    try {
        await fetch(`/api/admin/contact/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ status }),
        })
        loadMessages(pagination.value.current_page)
    } catch (e) {
        console.error('Failed to update status:', e)
    }
}

function openDetail(msg) {
    detailMessage.value = msg
    detailStatus.value = msg.status

    // Auto-mark as read if pending
    if (msg.status === 'pending') {
        updateStatus(msg.id, 'read')
        msg.status = 'read'
        detailStatus.value = 'read'
    }
}

async function saveDetailStatus() {
    if (!detailMessage.value) return

    detailSaving.value = true
    try {
        await fetch(`/api/admin/contact/${detailMessage.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ status: detailStatus.value }),
        })
        detailMessage.value = null
        loadMessages(pagination.value.current_page)
    } catch (e) {
        console.error('Failed to save:', e)
    } finally {
        detailSaving.value = false
    }
}

async function deleteMessage(id) {
    if (!confirm('Are you sure you want to delete this message?')) return

    try {
        await fetch(`/api/admin/contact/${id}`, {
            method: 'DELETE',
            credentials: 'include',
        })
        loadMessages(pagination.value.current_page)
    } catch (e) {
        console.error('Failed to delete:', e)
    }
}

onMounted(() => {
    loadMessages()
})
</script>
