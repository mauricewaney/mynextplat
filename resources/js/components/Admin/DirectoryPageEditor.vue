<template>
    <AdminLayout>
        <div v-if="page" class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <router-link to="/admin/directory-pages" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </router-link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        <span :class="typeBadgeClass(page.directory_type)" class="px-2 py-0.5 rounded text-xs font-medium mr-2">{{ page.directory_type }}</span>
                        {{ page.slug }}
                    </h1>
                </div>
                <a :href="liveUrl" target="_blank" class="text-sm text-primary-600 hover:text-primary-500 flex items-center gap-1">
                    View live page
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>

            <!-- Intro Text -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Intro Text</h2>
                <p v-if="fallbackIntro" class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                    PHP fallback: <em>{{ fallbackIntro.substring(0, 100) }}...</em>
                </p>
                <textarea
                    v-model="page.intro_text"
                    rows="4"
                    placeholder="Override the default intro text (leave empty to use PHP constant fallback)"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm"
                ></textarea>
                <div class="flex justify-end mt-2">
                    <button @click="savePage" :disabled="saving" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium disabled:opacity-50">
                        {{ saving ? 'Saving...' : 'Save Intro' }}
                    </button>
                </div>
            </div>

            <!-- Featured Games -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Featured Games</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Pick up to 10 games to feature at the top. Leave empty to use the top-5 by critic score.</p>

                <!-- Game search -->
                <div class="relative mb-3">
                    <input
                        v-model="gameSearchQuery"
                        @input="searchGames"
                        type="text"
                        placeholder="Search games to add..."
                        class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm"
                    />
                    <div v-if="gameSearchResults.length > 0" class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                        <button
                            v-for="game in gameSearchResults"
                            :key="game.id"
                            @click="addFeaturedGame(game)"
                            class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-slate-600 text-sm flex items-center gap-2"
                        >
                            <img v-if="game.cover_url" :src="game.cover_url" class="w-8 h-10 object-cover rounded" />
                            <div v-else class="w-8 h-10 bg-slate-600 rounded"></div>
                            <span class="text-gray-900 dark:text-white">{{ game.title }}</span>
                            <span class="text-xs text-gray-500 ml-auto">#{{ game.id }}</span>
                        </button>
                    </div>
                </div>

                <!-- Featured games list -->
                <div class="space-y-2">
                    <div
                        v-for="(game, idx) in featuredGames"
                        :key="game.id"
                        class="flex items-center gap-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg px-3 py-2"
                    >
                        <span class="text-xs text-gray-400 w-5 text-center">{{ idx + 1 }}</span>
                        <img v-if="game.cover_url" :src="game.cover_url" class="w-8 h-10 object-cover rounded" />
                        <div v-else class="w-8 h-10 bg-slate-600 rounded"></div>
                        <span class="text-sm text-gray-900 dark:text-white flex-1">{{ game.title }}</span>
                        <button v-if="idx > 0" @click="moveFeatured(idx, -1)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <button v-if="idx < featuredGames.length - 1" @click="moveFeatured(idx, 1)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <button @click="removeFeatured(idx)" class="text-red-400 hover:text-red-500 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end mt-3">
                    <button @click="saveFeatured" :disabled="saving" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium disabled:opacity-50">
                        {{ saving ? 'Saving...' : 'Save Featured' }}
                    </button>
                </div>
            </div>

            <!-- Sections -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Curated Sections</h2>
                    <button @click="openSectionModal(null)" class="px-3 py-1.5 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium">
                        Add Section
                    </button>
                </div>

                <div v-if="page.sections?.length" class="space-y-2">
                    <div
                        v-for="(section, idx) in page.sections"
                        :key="section.id"
                        class="flex items-center gap-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg px-4 py-3"
                    >
                        <span class="text-xs text-gray-400 w-5">{{ idx + 1 }}</span>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ section.title }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <span v-if="section.game_ids?.length && section.filter_definition">Hybrid</span>
                                <span v-else-if="section.game_ids?.length">Manual</span>
                                <span v-else-if="section.filter_definition">Filter</span>
                                <span v-else>Empty</span>
                                &middot; Limit: {{ section.limit }}
                            </div>
                        </div>
                        <button @click="previewSection(section)" class="text-gray-400 hover:text-primary-500 text-sm" title="Preview">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                        <button @click="openSectionModal(section)" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                            Edit
                        </button>
                        <button @click="deleteSection(section)" class="text-red-500 hover:text-red-400 text-sm font-medium">
                            Delete
                        </button>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400">No sections yet. Add one to curate sub-lists within this page.</p>
            </div>

            <!-- Related Pages -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Related Pages</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Cross-link to other directory pages. Displayed as linked badges.</p>

                <div class="flex gap-2 mb-3">
                    <select v-model="newRelated.type" class="px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm">
                        <option value="genre">Genre</option>
                        <option value="platform">Platform</option>
                        <option value="preset">Preset</option>
                    </select>
                    <input v-model="newRelated.slug" placeholder="slug" class="px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm flex-1" />
                    <input v-model="newRelated.label" placeholder="Label" class="px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm flex-1" />
                    <button @click="addRelated" :disabled="!newRelated.slug || !newRelated.label" class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm disabled:opacity-50">
                        Add
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="(rp, idx) in relatedPages"
                        :key="idx"
                        class="inline-flex items-center gap-1.5 bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded-full text-sm"
                    >
                        <span class="text-[0.65rem] text-gray-500">{{ rp.type }}:</span>
                        {{ rp.label }}
                        <button @click="removeRelated(idx)" class="text-red-400 hover:text-red-500 ml-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                    <span v-if="!relatedPages.length" class="text-sm text-gray-500 dark:text-gray-400">None</span>
                </div>

                <div class="flex justify-end mt-3">
                    <button @click="saveRelated" :disabled="saving" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium disabled:opacity-50">
                        {{ saving ? 'Saving...' : 'Save Related' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">Loading...</div>

        <!-- Section Modal -->
        <DirectorySectionModal
            v-if="showSectionModal"
            :page-id="page.id"
            :section="editingSection"
            @close="showSectionModal = false"
            @saved="onSectionSaved"
        />

        <!-- Preview Modal -->
        <Transition name="modal">
            <div v-if="previewGames" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="previewGames = null">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[80vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Section Preview</h3>
                        <button @click="previewGames = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div v-for="game in previewGames" :key="game.id" class="flex items-center gap-3 py-2 border-b border-gray-100 dark:border-slate-700 last:border-0">
                        <img v-if="game.cover_url" :src="game.cover_url" class="w-10 h-13 object-cover rounded" />
                        <div v-else class="w-10 h-13 bg-slate-600 rounded"></div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ game.title }}</div>
                            <div class="text-xs text-gray-500">
                                <span v-if="game.difficulty">Diff: {{ game.difficulty }}/10</span>
                                <span v-if="game.time_range"> &middot; {{ game.time_range }}</span>
                                <span v-if="game.critic_score"> &middot; Critic: {{ game.critic_score }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-if="previewGames.length === 0" class="text-sm text-gray-500 py-4 text-center">No games matched.</p>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from './AdminLayout.vue'
import DirectorySectionModal from './DirectorySectionModal.vue'

const route = useRoute()
const page = ref(null)
const saving = ref(false)
const showSectionModal = ref(false)
const editingSection = ref(null)
const previewGames = ref(null)

// Featured games
const featuredGames = ref([])
const gameSearchQuery = ref('')
const gameSearchResults = ref([])
let searchTimeout = null

// Related pages
const relatedPages = ref([])
const newRelated = ref({ type: 'genre', slug: '', label: '' })

// Fallback intro hints
const fallbackIntro = ref('')

const liveUrl = computed(() => {
    if (!page.value) return '/'
    const p = page.value
    if (p.directory_type === 'genre') return `/games/genre/${p.slug}`
    if (p.directory_type === 'platform') return `/games/platform/${p.slug}`
    return `/guides/${p.slug}`
})

function typeBadgeClass(type) {
    return {
        'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300': type === 'genre',
        'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300': type === 'platform',
        'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300': type === 'preset',
    }
}

async function loadPage() {
    const res = await fetch(`/api/admin/directory-pages/${route.params.id}`, { credentials: 'include' })
    if (res.ok) {
        page.value = await res.json()
        relatedPages.value = page.value.related_pages || []
        await loadFeaturedGames()
    }
}

async function loadFeaturedGames() {
    const ids = page.value.featured_game_ids || []
    if (ids.length === 0) {
        featuredGames.value = []
        return
    }
    // Load game details for the featured IDs
    const games = []
    for (const id of ids) {
        const res = await fetch(`/api/games/${id}`, { credentials: 'include' })
        if (res.ok) {
            const game = await res.json()
            games.push(game)
        }
    }
    featuredGames.value = games
}

function searchGames() {
    clearTimeout(searchTimeout)
    if (gameSearchQuery.value.length < 2) {
        gameSearchResults.value = []
        return
    }
    searchTimeout = setTimeout(async () => {
        const res = await fetch(`/api/games?search=${encodeURIComponent(gameSearchQuery.value)}&per_page=8`, { credentials: 'include' })
        if (res.ok) {
            const data = await res.json()
            gameSearchResults.value = data.data || []
        }
    }, 300)
}

function addFeaturedGame(game) {
    if (featuredGames.value.length >= 10) return
    if (featuredGames.value.some(g => g.id === game.id)) return
    featuredGames.value.push(game)
    gameSearchQuery.value = ''
    gameSearchResults.value = []
}

function removeFeatured(idx) {
    featuredGames.value.splice(idx, 1)
}

function moveFeatured(idx, dir) {
    const newIdx = idx + dir
    const temp = featuredGames.value[idx]
    featuredGames.value[idx] = featuredGames.value[newIdx]
    featuredGames.value[newIdx] = temp
    featuredGames.value = [...featuredGames.value]
}

async function savePage() {
    saving.value = true
    const res = await fetch(`/api/admin/directory-pages/${page.value.id}`, {
        method: 'PUT',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ intro_text: page.value.intro_text || null }),
    })
    if (res.ok) page.value = await res.json()
    saving.value = false
}

async function saveFeatured() {
    saving.value = true
    const ids = featuredGames.value.map(g => g.id)
    const res = await fetch(`/api/admin/directory-pages/${page.value.id}`, {
        method: 'PUT',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ featured_game_ids: ids.length ? ids : null }),
    })
    if (res.ok) page.value = await res.json()
    saving.value = false
}

async function saveRelated() {
    saving.value = true
    const res = await fetch(`/api/admin/directory-pages/${page.value.id}`, {
        method: 'PUT',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ related_pages: relatedPages.value.length ? relatedPages.value : null }),
    })
    if (res.ok) page.value = await res.json()
    saving.value = false
}

function addRelated() {
    relatedPages.value.push({ ...newRelated.value })
    newRelated.value = { type: 'genre', slug: '', label: '' }
}

function removeRelated(idx) {
    relatedPages.value.splice(idx, 1)
}

function openSectionModal(section) {
    editingSection.value = section
    showSectionModal.value = true
}

async function onSectionSaved() {
    showSectionModal.value = false
    await loadPage()
}

async function deleteSection(section) {
    if (!confirm(`Delete section "${section.title}"?`)) return
    const res = await fetch(`/api/admin/directory-pages/${page.value.id}/sections/${section.id}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: { 'Accept': 'application/json' },
    })
    if (res.ok) await loadPage()
}

async function previewSection(section) {
    const res = await fetch(`/api/admin/directory-pages/${page.value.id}/sections/${section.id}/preview`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Accept': 'application/json' },
    })
    if (res.ok) previewGames.value = await res.json()
}

onMounted(loadPage)
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
