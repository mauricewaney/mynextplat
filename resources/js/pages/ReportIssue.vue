<template>
    <AppLayout title="Report an Issue">
        <div class="max-w-3xl mx-auto px-4 py-8">
            <!-- Success Message -->
            <div v-if="submitted" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-8 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 dark:bg-emerald-900/50 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Thank You!</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Your correction has been submitted and will be reviewed by our team.
                </p>
                <div class="flex justify-center gap-3">
                    <router-link
                        to="/"
                        class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors"
                    >
                        Back to Home
                    </router-link>
                    <button
                        @click="resetForm"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors"
                    >
                        Report Another Issue
                    </button>
                </div>
            </div>

            <!-- Form -->
            <form v-else @submit.prevent="submitForm" class="space-y-6">
                <!-- Info Box -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Help us improve our data! If you notice incorrect information about a game, let us know and we'll fix it.
                        </p>
                    </div>
                </div>

                <!-- Game Selection -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Which game has the issue? <span class="text-red-500">*</span>
                    </label>

                    <!-- Selected Game -->
                    <div v-if="selectedGame" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                        <img
                            v-if="selectedGame.cover_url"
                            :src="selectedGame.cover_url"
                            :alt="selectedGame.title"
                            class="w-12 h-16 object-cover rounded"
                        />
                        <div v-else class="w-12 h-16 bg-gray-200 dark:bg-slate-600 rounded flex items-center justify-center">
                            <span class="text-gray-400 text-xs">?</span>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900 dark:text-white">{{ selectedGame.title }}</div>
                            <router-link
                                :to="`/game/${selectedGame.slug}`"
                                class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                                target="_blank"
                            >
                                View game page
                            </router-link>
                        </div>
                        <button
                            type="button"
                            @click="clearGame"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Game Search -->
                    <div v-else class="relative">
                        <input
                            v-model="gameSearch"
                            type="text"
                            placeholder="Search for a game..."
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            @input="searchGames"
                        />
                        <!-- Loading -->
                        <div v-if="searchLoading" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg class="w-5 h-5 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        <!-- Results Dropdown -->
                        <div
                            v-if="searchResults.length > 0"
                            class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                        >
                            <button
                                v-for="game in searchResults"
                                :key="game.id"
                                type="button"
                                @click="selectGame(game)"
                                class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors text-left"
                            >
                                <img
                                    v-if="game.cover_url"
                                    :src="game.cover_url"
                                    :alt="game.title"
                                    class="w-10 h-14 object-cover rounded"
                                />
                                <div v-else class="w-10 h-14 bg-gray-200 dark:bg-slate-500 rounded flex items-center justify-center">
                                    <span class="text-gray-400 text-xs">?</span>
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ game.title }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Category -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        What type of issue is it? <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <button
                            v-for="(label, key) in categories"
                            :key="key"
                            type="button"
                            @click="form.category = key"
                            :class="[
                                'px-4 py-3 rounded-lg text-sm font-medium transition-all text-center',
                                form.category === key
                                    ? 'bg-primary-600 text-white ring-2 ring-primary-600 ring-offset-2 dark:ring-offset-slate-800'
                                    : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'
                            ]"
                        >
                            {{ label }}
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        <template v-if="form.category === 'trophy_data'">Difficulty rating, completion time, playthroughs required, online/missable trophies</template>
                        <template v-else-if="form.category === 'game_info'">Title, developer, publisher, release date, description</template>
                        <template v-else-if="form.category === 'guide_links'">Wrong guide URL, dead link, or suggest a new guide</template>
                        <template v-else-if="form.category === 'images'">Wrong cover art or banner image</template>
                        <template v-else-if="form.category === 'other'">Anything else not covered above</template>
                        <template v-else>Select a category to see what it covers</template>
                    </p>
                </div>

                <!-- Description -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Describe the issue <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        placeholder="What's wrong and what should it be? Be as specific as possible..."
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
                        maxlength="2000"
                    ></textarea>
                    <div class="mt-1 text-xs text-gray-400 text-right">{{ form.description.length }}/2000</div>
                </div>

                <!-- Source URL (Optional) -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Source URL <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input
                        v-model="form.source_url"
                        type="url"
                        placeholder="https://psnprofiles.com/..."
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Link to a page that shows the correct information (helps us verify faster)
                    </p>
                </div>

                <!-- Email for Guests -->
                <div v-if="!isAuthenticated" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Your email <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="your@email.com"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        In case we need to follow up with questions
                    </p>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-red-700 dark:text-red-300">{{ error }}</p>
                    </div>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="!canSubmit || submitting"
                    class="w-full py-4 bg-primary-600 text-white rounded-xl font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <svg v-if="submitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>{{ submitting ? 'Submitting...' : 'Submit Correction' }}</span>
                </button>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import AppLayout from '../components/AppLayout.vue'

const route = useRoute()
let recaptchaScript = null
const { isAuthenticated, user } = useAuth()

const categories = ref({})
const selectedGame = ref(null)
const gameSearch = ref('')
const searchResults = ref([])
const searchLoading = ref(false)
const submitting = ref(false)
const submitted = ref(false)
const error = ref('')

const form = reactive({
    category: '',
    description: '',
    source_url: '',
    email: '',
})

const canSubmit = computed(() => {
    return selectedGame.value && form.category && form.description.length >= 10
})

let searchDebounce = null

async function loadCategories() {
    try {
        const response = await fetch('/api/corrections/categories')
        categories.value = await response.json()
    } catch (e) {
        console.error('Failed to load categories:', e)
    }
}

async function searchGames() {
    clearTimeout(searchDebounce)

    if (gameSearch.value.length < 2) {
        searchResults.value = []
        return
    }

    searchDebounce = setTimeout(async () => {
        searchLoading.value = true
        try {
            const response = await fetch(`/api/corrections/search-games?q=${encodeURIComponent(gameSearch.value)}`)
            searchResults.value = await response.json()
        } catch (e) {
            console.error('Failed to search games:', e)
        } finally {
            searchLoading.value = false
        }
    }, 300)
}

function selectGame(game) {
    selectedGame.value = game
    gameSearch.value = ''
    searchResults.value = []
}

function clearGame() {
    selectedGame.value = null
}

async function loadGameFromRoute() {
    const gameId = route.query.game
    if (!gameId) return

    try {
        const response = await fetch(`/api/games/${gameId}`)
        if (response.ok) {
            const game = await response.json()
            selectedGame.value = {
                id: game.id,
                title: game.title,
                slug: game.slug,
                cover_url: game.cover_url,
            }
        }
    } catch (e) {
        console.error('Failed to load game:', e)
    }
}

async function getRecaptchaToken() {
    // Only needed for guests
    if (isAuthenticated.value) return null

    const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY
    if (!siteKey) return null

    return new Promise((resolve) => {
        if (typeof grecaptcha === 'undefined') {
            resolve(null)
            return
        }
        grecaptcha.ready(() => {
            grecaptcha.execute(siteKey, { action: 'submit_correction' })
                .then(resolve)
                .catch(() => resolve(null))
        })
    })
}

async function submitForm() {
    if (!canSubmit.value || submitting.value) return

    submitting.value = true
    error.value = ''

    try {
        const recaptchaToken = await getRecaptchaToken()

        const payload = {
            game_id: selectedGame.value.id,
            category: form.category,
            description: form.description,
            source_url: form.source_url || null,
            email: form.email || null,
        }

        if (recaptchaToken) {
            payload.recaptcha_token = recaptchaToken
        }

        const response = await fetch('/api/corrections', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(payload),
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.message || 'Failed to submit correction')
        }

        submitted.value = true
    } catch (e) {
        error.value = e.message
    } finally {
        submitting.value = false
    }
}

function resetForm() {
    selectedGame.value = null
    form.category = ''
    form.description = ''
    form.source_url = ''
    form.email = ''
    submitted.value = false
    error.value = ''
}

function loadRecaptcha() {
    const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY
    if (!siteKey || isAuthenticated.value) return // Don't load for logged-in users

    // Check if already loaded
    if (document.querySelector('script[src*="recaptcha"]')) return

    recaptchaScript = document.createElement('script')
    recaptchaScript.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`
    recaptchaScript.async = true
    recaptchaScript.defer = true
    document.head.appendChild(recaptchaScript)
}

function unloadRecaptcha() {
    // Remove script
    if (recaptchaScript && recaptchaScript.parentNode) {
        recaptchaScript.parentNode.removeChild(recaptchaScript)
    }
    // Remove badge
    const badge = document.querySelector('.grecaptcha-badge')
    if (badge) {
        badge.parentNode.removeChild(badge)
    }
    // Clean up global
    if (window.grecaptcha) {
        delete window.grecaptcha
    }
}

onMounted(() => {
    loadCategories()
    loadGameFromRoute()
    loadRecaptcha()
})

onUnmounted(() => {
    unloadRecaptcha()
})
</script>
