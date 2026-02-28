<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Directory Pages</h1>
                <button @click="showAddModal = true" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium">
                    Add Page
                </button>
            </div>

            <!-- Filters -->
            <div class="flex gap-3">
                <select v-model="typeFilter" class="px-3 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg text-sm">
                    <option value="">All Types</option>
                    <option value="genre">Genre</option>
                    <option value="platform">Platform</option>
                    <option value="preset">Preset</option>
                </select>
                <input
                    v-model="slugSearch"
                    type="text"
                    placeholder="Search slugs..."
                    class="px-3 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg text-sm flex-1 max-w-xs"
                />
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Slug</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Intro</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Featured</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sections</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <tr v-for="page in filteredPages" :key="page.id" class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <span :class="typeBadgeClass(page.directory_type)" class="px-2 py-0.5 rounded text-xs font-medium">
                                    {{ page.directory_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono">{{ page.slug }}</td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="page.intro_text" class="text-green-500">&#10003;</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ page.featured_game_ids?.length || 0 }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ page.sections_count || 0 }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="liveUrl(page)" target="_blank" class="text-gray-400 hover:text-primary-500 text-sm" title="View live page">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                    <router-link :to="`/admin/directory-pages/${page.id}`" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                                        Edit
                                    </router-link>
                                    <button @click="deletePage(page)" class="text-red-500 hover:text-red-400 text-sm font-medium">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredPages.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No directory pages found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Page Modal -->
        <Transition name="modal">
            <div v-if="showAddModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showAddModal = false">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Add Directory Page</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select v-model="newPage.directory_type" @change="newPage.slug = ''" class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm">
                                <option value="">Select type...</option>
                                <option value="genre">Genre</option>
                                <option value="platform">Platform</option>
                                <option value="preset">Preset</option>
                            </select>
                        </div>

                        <div v-if="newPage.directory_type">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                            <select v-model="newPage.slug" class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm">
                                <option value="">Select slug...</option>
                                <option v-for="s in availableSlugOptions" :key="s.slug" :value="s.slug">
                                    {{ s.name }} ({{ s.slug }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="showAddModal = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white">
                            Cancel
                        </button>
                        <button @click="createPage" :disabled="!newPage.directory_type || !newPage.slug" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium disabled:opacity-50">
                            Create
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from './AdminLayout.vue'

const router = useRouter()
const pages = ref([])
const availableSlugs = ref({ genre: [], platform: [], preset: [] })
const typeFilter = ref('')
const slugSearch = ref('')
const showAddModal = ref(false)
const newPage = ref({ directory_type: '', slug: '' })

const filteredPages = computed(() => {
    return pages.value.filter(p => {
        if (typeFilter.value && p.directory_type !== typeFilter.value) return false
        if (slugSearch.value && !p.slug.includes(slugSearch.value.toLowerCase())) return false
        return true
    })
})

const availableSlugOptions = computed(() => {
    if (!newPage.value.directory_type) return []
    return availableSlugs.value[newPage.value.directory_type] || []
})

function typeBadgeClass(type) {
    return {
        'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300': type === 'genre',
        'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300': type === 'platform',
        'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300': type === 'preset',
    }
}

function liveUrl(page) {
    if (page.directory_type === 'genre') return `/games/genre/${page.slug}`
    if (page.directory_type === 'platform') return `/games/platform/${page.slug}`
    return `/guides/${page.slug}`
}

async function loadPages() {
    const res = await fetch('/api/admin/directory-pages', { credentials: 'include' })
    if (res.ok) pages.value = await res.json()
}

async function loadSlugs() {
    const res = await fetch('/api/admin/directory-pages/available-slugs', { credentials: 'include' })
    if (res.ok) availableSlugs.value = await res.json()
}

async function createPage() {
    const res = await fetch('/api/admin/directory-pages', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(newPage.value),
    })
    if (res.ok) {
        const page = await res.json()
        showAddModal.value = false
        router.push(`/admin/directory-pages/${page.id}`)
    }
}

async function deletePage(page) {
    if (!confirm(`Delete directory page "${page.directory_type}/${page.slug}"? This will also delete all its sections.`)) return
    const res = await fetch(`/api/admin/directory-pages/${page.id}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: { 'Accept': 'application/json' },
    })
    if (res.ok) {
        pages.value = pages.value.filter(p => p.id !== page.id)
    }
}

onMounted(() => {
    loadPages()
    loadSlugs()
})
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
