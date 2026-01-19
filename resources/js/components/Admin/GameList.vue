<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Games</h1>
                    <p class="text-gray-600 mt-1">Manage your PlayStation trophy database</p>
                </div>
                <button
                    @click="openAddModal"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center space-x-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add Game</span>
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Filters</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search games..."
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            @input="debouncedFetch"
                        />
                    </div>

                    <!-- Difficulty Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Difficulty ({{ filters.difficulty_min }} - {{ filters.difficulty_max }})
                        </label>
                        <div class="flex gap-2">
                            <input
                                v-model.number="filters.difficulty_min"
                                type="range"
                                min="1"
                                max="10"
                                class="w-full"
                                @change="onFilterChange"
                            />
                            <input
                                v-model.number="filters.difficulty_max"
                                type="range"
                                min="1"
                                max="10"
                                class="w-full"
                                @change="onFilterChange"
                            />
                        </div>
                    </div>

                    <!-- Time Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Time ({{ filters.time_min }}h - {{ filters.time_max }}h)
                        </label>
                        <div class="flex gap-2">
                            <input
                                v-model.number="filters.time_min"
                                type="range"
                                min="0"
                                max="200"
                                step="5"
                                class="w-full"
                                @change="onFilterChange"
                            />
                            <input
                                v-model.number="filters.time_max"
                                type="range"
                                min="0"
                                max="200"
                                step="5"
                                class="w-full"
                                @change="onFilterChange"
                            />
                        </div>
                    </div>

                    <!-- Max Playthroughs -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Playthroughs</label>
                        <input
                            v-model.number="filters.max_playthroughs"
                            type="number"
                            min="1"
                            placeholder="e.g., 1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Min Score -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Score</label>
                        <input
                            v-model.number="filters.min_score"
                            type="number"
                            min="0"
                            max="100"
                            placeholder="e.g., 80"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Genre Filter -->
                    <div class="relative" ref="genreDropdownRef">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Genres</label>
                        <button
                            type="button"
                            @click="showGenreDropdown = !showGenreDropdown"
                            class="w-full px-3 py-2 text-left bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <span v-if="filters.genre_ids.length === 0" class="text-gray-500">Select genres...</span>
                            <span v-else class="text-gray-900">{{ filters.genre_ids.length }} selected</span>
                            <svg class="absolute right-3 top-9 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            v-if="showGenreDropdown"
                            class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                        >
                            <label
                                v-for="genre in formData.genres"
                                :key="genre.id"
                                class="flex items-center px-3 py-2 hover:bg-gray-100 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="genre.id"
                                    v-model="filters.genre_ids"
                                    @change="onFilterChange"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">{{ genre.name }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Platform Filter -->
                    <div class="relative" ref="platformDropdownRef">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Platforms</label>
                        <button
                            type="button"
                            @click="showPlatformDropdown = !showPlatformDropdown"
                            class="w-full px-3 py-2 text-left bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <span v-if="filters.platform_ids.length === 0" class="text-gray-500">Select platforms...</span>
                            <span v-else class="text-gray-900">{{ filters.platform_ids.length }} selected</span>
                            <svg class="absolute right-3 top-9 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            v-if="showPlatformDropdown"
                            class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                        >
                            <label
                                v-for="platform in formData.platforms"
                                :key="platform.id"
                                class="flex items-center px-3 py-2 hover:bg-gray-100 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="platform.id"
                                    v-model="filters.platform_ids"
                                    @change="onFilterChange"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">{{ platform.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Boolean Filters -->
                <div class="flex flex-wrap gap-4 mt-4">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            v-model="filters.has_online_trophies"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            @change="onFilterChange"
                        />
                        <span class="text-sm">Has Online Trophies</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            v-model="filters.missable_trophies"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            @change="onFilterChange"
                        />
                        <span class="text-sm">Has Missable Trophies</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            v-model="filters.no_genres"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            @change="onFilterChange"
                        />
                        <span class="text-sm">No Genres</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            v-model="filters.no_platforms"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            @change="onFilterChange"
                        />
                        <span class="text-sm">No Platforms</span>
                    </label>
                </div>

                <!-- Clear Filters -->
                <div class="flex justify-end mt-4">
                    <button
                        @click="resetFilters"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md text-sm font-medium"
                    >
                        Reset Filters
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Toolbar -->
            <div v-if="selectedGames.length > 0" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-indigo-900">
              {{ selectedGames.length }} game(s) selected
            </span>
                        <button
                            @click="selectedGames = []"
                            class="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            Clear selection
                        </button>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <button
                            @click="bulkScrapeImages"
                            :disabled="bulkScraping"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
                        >
                            {{ bulkScraping ? 'Scraping...' : '📷 Scrape Images' }}
                        </button>
                        <button
                            @click="openGenreModal('add')"
                            class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50"
                        >
                            Add Genres
                        </button>
                        <button
                            @click="openGenreModal('remove')"
                            class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50"
                        >
                            Remove Genres
                        </button>
                        <button
                            @click="openTagModal('add')"
                            class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50"
                        >
                            Add Tags
                        </button>
                        <button
                            @click="openTagModal('remove')"
                            class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50"
                        >
                            Remove Tags
                        </button>
                        <button
                            @click="openPlatformModal('add')"
                            class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50"
                        >
                            Add Platforms
                        </button>
                        <button
                            @click="openPlatformModal('remove')"
                            class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50"
                        >
                            Remove Platforms
                        </button>
                        <button
                            @click="bulkDelete"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                        >
                            Delete Selected
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-600">Loading games...</p>
            </div>

            <!-- Games Table -->
            <div v-else-if="games.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="toggleSelectAll"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cover
                        </th>
                        <th
                            @click="sortBy('title')"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                        >
                            Title
                            <span v-if="filters.sort_by === 'title'">{{ filters.sort_order === 'asc' ? '↑' : '↓' }}</span>
                        </th>
                        <th
                            @click="sortBy('release_date')"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                        >
                            Release
                            <span v-if="filters.sort_by === 'release_date'">{{ filters.sort_order === 'asc' ? '↑' : '↓' }}</span>
                        </th>
                        <th
                            @click="sortBy('metacritic_score')"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                        >
                            Score
                            <span v-if="filters.sort_by === 'metacritic_score'">{{ filters.sort_order === 'asc' ? '↑' : '↓' }}</span>
                        </th>
                        <th
                            @click="sortBy('difficulty')"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                        >
                            Difficulty
                            <span v-if="filters.sort_by === 'difficulty'">{{ filters.sort_order === 'asc' ? '↑' : '↓' }}</span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Time
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Platforms
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="game in games"
                        :key="game.id"
                        :class="{ 'bg-indigo-50': selectedGames.includes(game.id) }"
                        class="hover:bg-gray-50"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input
                                type="checkbox"
                                :checked="selectedGames.includes(game.id)"
                                @change="toggleGameSelection(game.id)"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <img
                                v-if="game.cover_url"
                                :src="game.cover_url"
                                :alt="game.title"
                                class="w-12 h-16 object-cover rounded shadow-sm"
                            />
                            <div v-else class="w-12 h-16 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">
                                No Image
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ game.title }}</div>
                            <div class="text-sm text-gray-500">{{ game.developer }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ game.release_date ? formatDate(game.release_date) : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span v-if="game.best_score" class="font-medium">{{ game.best_score }}</span>
                            <span v-else class="text-gray-400">N/A</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
              <span
                  v-if="game.difficulty"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getDifficultyColor(game.difficulty)"
              >
                {{ game.difficulty }}/10
              </span>
                            <span v-else class="text-gray-400 text-sm">N/A</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ game.time_range || 'N/A' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="platform in game.platforms"
                                    :key="platform.id"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                                >
                                    {{ platform.short_name || platform.name }}
                                </span>
                                <span v-if="!game.platforms || game.platforms.length === 0" class="text-sm text-gray-400">-</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button
                                @click="openEditModal(game)"
                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                            >
                                Edit
                            </button>
                            <button
                                @click="scrapeImage(game)"
                                :disabled="scrapingGames.includes(game.id)"
                                class="text-green-600 hover:text-green-900 mr-3 disabled:opacity-50"
                                :title="game.cover_url ? 'Re-scrape image' : 'Scrape image from IGDB'"
                            >
                                <span v-if="scrapingGames.includes(game.id)">...</span>
                                <span v-else>{{ game.cover_url ? '🔄' : '📷' }}</span>
                            </button>
                            <button
                                @click="confirmDelete(game)"
                                class="text-red-600 hover:text-red-900"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white shadow rounded-lg p-12 text-center">
                <p class="text-gray-500">No games found. Try adjusting your filters or add your first game!</p>
            </div>

            <!-- Pagination -->
            <div v-if="games.length > 0" class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, total) }} of {{ total }} games
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        @click="goToPage(1)"
                        :disabled="currentPage === 1"
                        class="px-3 py-1 border rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                        First
                    </button>
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="px-3 py-1 border rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                        Previous
                    </button>
                    <span class="px-3 py-1 text-sm">
                        Page {{ currentPage }} of {{ lastPage }}
                    </span>
                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === lastPage"
                        class="px-3 py-1 border rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                        Next
                    </button>
                    <button
                        @click="goToPage(lastPage)"
                        :disabled="currentPage === lastPage"
                        class="px-3 py-1 border rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                    >
                        Last
                    </button>
                </div>
            </div>
        </div>

        <!-- Game Form Modal -->
        <GameFormModal
            :show="showGameModal"
            :game="editingGame"
            :genres="formData.genres"
            :tags="formData.tags"
            :platforms="formData.platforms"
            @close="closeGameModal"
            @saved="handleGameSaved"
        />

        <!-- Bulk Action Modals (Genre/Tag/Platform) -->
        <BulkActionModal
            :show="showGenreModal"
            :title="bulkAction === 'add' ? 'Add Genres' : 'Remove Genres'"
            @close="showGenreModal = false"
            @confirm="submitGenreModal"
        >
            <div class="space-y-2">
                <label
                    v-for="genre in formData.genres"
                    :key="genre.id"
                    class="flex items-center hover:bg-gray-50 p-2 rounded cursor-pointer"
                >
                    <input
                        type="checkbox"
                        :value="genre.id"
                        v-model="selectedModalItems"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span class="ml-2 text-sm text-gray-700">{{ genre.name }}</span>
                </label>
            </div>
        </BulkActionModal>

        <BulkActionModal
            :show="showTagModal"
            :title="bulkAction === 'add' ? 'Add Tags' : 'Remove Tags'"
            @close="showTagModal = false"
            @confirm="submitTagModal"
        >
            <div class="space-y-2">
                <label
                    v-for="tag in formData.tags"
                    :key="tag.id"
                    class="flex items-center hover:bg-gray-50 p-2 rounded cursor-pointer"
                >
                    <input
                        type="checkbox"
                        :value="tag.id"
                        v-model="selectedModalItems"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span class="ml-2 text-sm text-gray-700">{{ tag.name }}</span>
                </label>
            </div>
        </BulkActionModal>

        <BulkActionModal
            :show="showPlatformModal"
            :title="bulkAction === 'add' ? 'Add Platforms' : 'Remove Platforms'"
            @close="showPlatformModal = false"
            @confirm="submitPlatformModal"
        >
            <div class="space-y-2">
                <label
                    v-for="platform in formData.platforms"
                    :key="platform.id"
                    class="flex items-center hover:bg-gray-50 p-2 rounded cursor-pointer"
                >
                    <input
                        type="checkbox"
                        :value="platform.id"
                        v-model="selectedModalItems"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span class="ml-2 text-sm text-gray-700">{{ platform.name }}</span>
                </label>
            </div>
        </BulkActionModal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import AdminLayout from './AdminLayout.vue'
import GameFormModal from './GameFormModal.vue'
import BulkActionModal from './BulkActionModal.vue'

const games = ref([])
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = 50
const loading = ref(false)
const selectedGames = ref([])
const scrapingGames = ref([]) // Track which games are currently being scraped
const bulkScraping = ref(false)

// Form data (genres, tags, platforms)
const formData = reactive({
    genres: [],
    tags: [],
    platforms: []
})

// Filters
const filters = reactive({
    search: '',
    difficulty_min: 1,
    difficulty_max: 10,
    time_min: 0,
    time_max: 200,
    max_playthroughs: null,
    min_score: null,
    genre_ids: [],
    platform_ids: [],
    has_online_trophies: false,
    missable_trophies: false,
    no_genres: false,
    no_platforms: false,
    sort_by: 'created_at',
    sort_order: 'desc'
})

// Game Modal
const showGameModal = ref(false)
const editingGame = ref(null)

// Bulk action modals
const showGenreModal = ref(false)
const showTagModal = ref(false)
const showPlatformModal = ref(false)
const bulkAction = ref('')
const selectedModalItems = ref([])

// Filter dropdowns
const showGenreDropdown = ref(false)
const showPlatformDropdown = ref(false)
const genreDropdownRef = ref(null)
const platformDropdownRef = ref(null)

// Computed
const allSelected = computed(() => {
    return games.value.length > 0 && selectedGames.value.length === games.value.length
})

// Debounce search input
let debounceTimeout = null
const debouncedFetch = () => {
    clearTimeout(debounceTimeout)
    debounceTimeout = setTimeout(() => {
        fetchGames(true) // Reset to page 1 on search
    }, 300)
}

// Wrapper for filter changes that resets pagination
const onFilterChange = () => {
    fetchGames(true)
}

// Fetch form data
const fetchFormData = async () => {
    try {
        const response = await fetch('/api/admin/games/form-data')
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        const data = await response.json()

        formData.genres = Array.isArray(data.genres) ? data.genres : []
        formData.tags = Array.isArray(data.tags) ? data.tags : []
        formData.platforms = Array.isArray(data.platforms) ? data.platforms : []
    } catch (error) {
        console.error('Error fetching form data:', error)
    }
}

// Fetch games with filters
const fetchGames = async (resetPage = false) => {
    if (resetPage) {
        currentPage.value = 1
    }

    loading.value = true
    try {
        const params = new URLSearchParams()
        params.append('page', currentPage.value)

        Object.keys(filters).forEach(key => {
            const value = filters[key]
            if (Array.isArray(value) && value.length > 0) {
                value.forEach(v => params.append(`${key}[]`, v))
            } else if (typeof value === 'boolean') {
                if (value) params.append(key, '1')
            } else if (value !== '' && value !== null) {
                params.append(key, value)
            }
        })

        const response = await fetch(`/api/admin/games?${params.toString()}`)
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        const data = await response.json()
        games.value = Array.isArray(data.data) ? data.data : []
        total.value = data.total || games.value.length
        currentPage.value = data.current_page || 1
        lastPage.value = data.last_page || 1
    } catch (error) {
        console.error('Error fetching games:', error)
        games.value = []
    } finally {
        loading.value = false
    }
}

// Go to specific page
const goToPage = (page) => {
    if (page < 1 || page > lastPage.value) return
    currentPage.value = page
    fetchGames()
    selectedGames.value = [] // Clear selection when changing pages
}

// Reset filters
const resetFilters = () => {
    filters.search = ''
    filters.difficulty_min = 1
    filters.difficulty_max = 10
    filters.time_min = 0
    filters.time_max = 200
    filters.max_playthroughs = null
    filters.min_score = null
    filters.genre_ids = []
    filters.platform_ids = []
    filters.has_online_trophies = false
    filters.missable_trophies = false
    filters.no_genres = false
    filters.no_platforms = false
    fetchGames(true) // Reset to page 1
}

// Get difficulty color
const getDifficultyColor = (difficulty) => {
    if (!difficulty) return 'bg-gray-100 text-gray-800'
    if (difficulty <= 3) return 'bg-green-100 text-green-800'
    if (difficulty <= 6) return 'bg-yellow-100 text-yellow-800'
    if (difficulty <= 8) return 'bg-orange-100 text-orange-800'
    return 'bg-red-100 text-red-800'
}

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// Sort by column
const sortBy = (column) => {
    if (filters.sort_by === column) {
        // Toggle sort order
        filters.sort_order = filters.sort_order === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by = column
        filters.sort_order = 'desc' // Default to descending for new column
    }
    fetchGames(true)
}

// Game CRUD
function openAddModal() {
    editingGame.value = null
    showGameModal.value = true
}

function openEditModal(game) {
    editingGame.value = game
    showGameModal.value = true
}

function closeGameModal() {
    showGameModal.value = false
    editingGame.value = null
}

function handleGameSaved() {
    fetchGames()
    selectedGames.value = []
}

function confirmDelete(game) {
    if (confirm(`Are you sure you want to delete "${game.title}"?`)) {
        deleteGame(game.id)
    }
}

async function deleteGame(id) {
    try {
        const response = await fetch(`/api/admin/games/${id}`, {
            method: 'DELETE'
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        alert(data.message || 'Game deleted successfully')
        fetchGames()
        selectedGames.value = selectedGames.value.filter(gameId => gameId !== id)
    } catch (error) {
        console.error('Error deleting game:', error)
        alert('Failed to delete game')
    }
}

// Image Scraping
async function scrapeImage(game) {
    scrapingGames.value.push(game.id)

    try {
        const response = await fetch(`/api/admin/games/${game.id}/scrape-image`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()

        if (data.success) {
            alert(`✅ Image scraped successfully for "${game.title}"!\nMatched: ${data.igdb_match || game.title}`)
            fetchGames() // Refresh to show new image
        } else {
            alert(`❌ ${data.message}`)
        }
    } catch (error) {
        console.error('Error scraping image:', error)
        alert('Failed to scrape image')
    } finally {
        scrapingGames.value = scrapingGames.value.filter(id => id !== game.id)
    }
}

async function bulkScrapeImages() {
    if (!confirm(`Scrape images for ${selectedGames.value.length} game(s)? This may take a while.`)) {
        return
    }

    bulkScraping.value = true

    try {
        const response = await fetch('/api/admin/games/bulk-scrape-images', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                game_ids: selectedGames.value
            })
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        alert(data.message)

        if (data.results && data.results.failed.length > 0) {
            console.log('Failed games:', data.results.failed)
        }

        fetchGames()
        selectedGames.value = []
    } catch (error) {
        console.error('Error bulk scraping images:', error)
        alert('Failed to bulk scrape images')
    } finally {
        bulkScraping.value = false
    }
}

// Selection
const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedGames.value = []
    } else {
        selectedGames.value = games.value.map(g => g.id)
    }
}

const toggleGameSelection = (gameId) => {
    const index = selectedGames.value.indexOf(gameId)
    if (index > -1) {
        selectedGames.value.splice(index, 1)
    } else {
        selectedGames.value.push(gameId)
    }
}

// Bulk Actions
const bulkDelete = async () => {
    if (!confirm(`Are you sure you want to delete ${selectedGames.value.length} game(s)?`)) {
        return
    }

    try {
        const response = await fetch('/api/admin/games/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                game_ids: selectedGames.value
            })
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        alert(data.message)
        selectedGames.value = []
        fetchGames()
    } catch (error) {
        console.error('Error deleting games:', error)
        alert('Failed to delete games')
    }
}

// Open modals
const openGenreModal = (action) => {
    bulkAction.value = action
    selectedModalItems.value = []
    showGenreModal.value = true
}

const openTagModal = (action) => {
    bulkAction.value = action
    selectedModalItems.value = []
    showTagModal.value = true
}

const openPlatformModal = (action) => {
    bulkAction.value = action
    selectedModalItems.value = []
    showPlatformModal.value = true
}

// Submit modals
const submitGenreModal = async () => {
    if (selectedModalItems.value.length === 0) {
        alert('Please select at least one genre')
        return
    }

    try {
        const endpoint = bulkAction.value === 'add' ? 'bulk-add-genres' : 'bulk-remove-genres'
        const response = await fetch(`/api/admin/games/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                game_ids: selectedGames.value,
                genre_ids: selectedModalItems.value
            })
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        alert(data.message)
        fetchGames()
        showGenreModal.value = false
    } catch (error) {
        console.error('Error with genres:', error)
        alert('Failed to update genres')
    }
}

const submitTagModal = async () => {
    if (selectedModalItems.value.length === 0) {
        alert('Please select at least one tag')
        return
    }

    try {
        const endpoint = bulkAction.value === 'add' ? 'bulk-add-tags' : 'bulk-remove-tags'
        const response = await fetch(`/api/admin/games/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                game_ids: selectedGames.value,
                tag_ids: selectedModalItems.value
            })
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        alert(data.message)
        fetchGames()
        showTagModal.value = false
    } catch (error) {
        console.error('Error with tags:', error)
        alert('Failed to update tags')
    }
}

const submitPlatformModal = async () => {
    if (selectedModalItems.value.length === 0) {
        alert('Please select at least one platform')
        return
    }

    try {
        const endpoint = bulkAction.value === 'add' ? 'bulk-add-platforms' : 'bulk-remove-platforms'
        const response = await fetch(`/api/admin/games/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                game_ids: selectedGames.value,
                platform_ids: selectedModalItems.value
            })
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        alert(data.message)
        fetchGames()
        showPlatformModal.value = false
    } catch (error) {
        console.error('Error with platforms:', error)
        alert('Failed to update platforms')
    }
}

// Load data on mount
onMounted(() => {
    fetchFormData()
    fetchGames()
})
</script>
