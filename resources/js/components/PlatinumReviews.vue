<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 mb-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Platinum Reviews</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    From players who earned the platinum
                </p>
            </div>
            <div v-if="averageRating" class="flex items-center gap-2">
                <div class="flex items-center gap-0.5">
                    <svg
                        v-for="star in 5"
                        :key="star"
                        class="w-5 h-5"
                        :class="star <= Math.round(averageRating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    {{ averageRating }}
                </span>
                <span class="text-sm text-gray-400 dark:text-gray-500">
                    ({{ totalCount }})
                </span>
            </div>
        </div>

        <!-- Review Form -->
        <div v-if="canReview || userReview" class="mb-6">
            <div v-if="!showForm && !userReview" class="text-center">
                <button
                    @click="showForm = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors text-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Write a Review
                </button>
            </div>

            <!-- Existing review display with edit option -->
            <div v-else-if="userReview && !showForm" class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Your Review</span>
                        <div class="flex items-center gap-0.5">
                            <svg
                                v-for="star in 5"
                                :key="star"
                                class="w-4 h-4"
                                :class="star <= userReview.rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="startEdit"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                        >Edit</button>
                        <button
                            @click="deleteReview"
                            :disabled="submitting"
                            class="text-xs text-red-500 hover:underline"
                        >Delete</button>
                    </div>
                </div>
                <p v-if="userReview.body" class="text-sm text-gray-600 dark:text-gray-400">{{ userReview.body }}</p>
            </div>

            <!-- Review form (new or edit) -->
            <div v-if="showForm" class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-4">
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rating</label>
                    <div class="flex items-center gap-1">
                        <button
                            v-for="star in 5"
                            :key="star"
                            @click="formRating = star"
                            class="focus:outline-none transition-colors"
                        >
                            <svg
                                class="w-8 h-8 cursor-pointer"
                                :class="star <= (hoverRating || formRating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                                @mouseenter="hoverRating = star"
                                @mouseleave="hoverRating = 0"
                            >
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Review <span class="font-normal text-gray-400">(optional)</span>
                    </label>
                    <textarea
                        v-model="formBody"
                        rows="3"
                        maxlength="2000"
                        placeholder="Share your thoughts on the platinum journey..."
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                    ></textarea>
                    <div class="text-xs text-gray-400 dark:text-gray-500 text-right mt-0.5">
                        {{ formBody.length }}/2000
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="submitReview"
                        :disabled="!formRating || submitting"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isEditing ? 'Update Review' : 'Submit Review' }}
                    </button>
                    <button
                        @click="cancelForm"
                        class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
                    >Cancel</button>
                </div>
                <p v-if="formError" class="text-sm text-red-500 mt-2">{{ formError }}</p>
            </div>
        </div>

        <!-- Auth/status prompts -->
        <div v-else-if="!isAuthenticated" class="mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <button @click="loginWithGoogle" class="text-primary-600 dark:text-primary-400 hover:underline">Sign in</button>
                to leave a platinum review
            </p>
        </div>
        <div v-else-if="userStatus && userStatus !== 'platinumed'" class="mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Earn the platinum trophy to leave a review
            </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
        </div>

        <!-- Reviews List -->
        <div v-else-if="reviews.length" class="space-y-4">
            <div
                v-for="review in reviews"
                :key="review.id"
                class="border-t border-gray-100 dark:border-slate-700 pt-4 first:border-t-0 first:pt-0"
            >
                <div class="flex items-center gap-3 mb-2">
                    <img
                        v-if="review.user.avatar"
                        :src="review.user.avatar"
                        :alt="review.user.name"
                        class="w-8 h-8 rounded-full"
                        @error="$event.target.style.display = 'none'"
                    />
                    <div
                        v-else
                        class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white text-sm font-bold"
                    >
                        {{ review.user.name?.charAt(0)?.toUpperCase() || '?' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <a
                                v-if="review.user.profile_slug"
                                :href="`/u/${review.user.profile_slug}`"
                                class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400"
                            >{{ review.user.name }}</a>
                            <span v-else class="text-sm font-medium text-gray-900 dark:text-white">{{ review.user.name }}</span>
                            <div class="flex items-center gap-0.5">
                                <svg
                                    v-for="star in 5"
                                    :key="star"
                                    class="w-3.5 h-3.5"
                                    :class="star <= review.rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(review.created_at) }}</span>
                    </div>
                </div>
                <p v-if="review.body" class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed ml-11">{{ review.body }}</p>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="!loading" class="text-center py-6">
            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-sm text-gray-400 dark:text-gray-500">No platinum reviews yet</p>
            <p v-if="canReview" class="text-xs text-gray-300 dark:text-gray-600 mt-1">Be the first to share your experience!</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import { apiGet, apiPost, apiPut, apiDelete } from '../utils/api'

const props = defineProps({
    gameId: Number,
    gameSlug: String,
    userStatus: String,
})

const { isAuthenticated, user, loginWithGoogle } = useAuth()

const reviews = ref([])
const averageRating = ref(null)
const totalCount = ref(0)
const loading = ref(true)
const submitting = ref(false)
const showForm = ref(false)
const formRating = ref(0)
const formBody = ref('')
const formError = ref(null)
const hoverRating = ref(0)
const isEditing = ref(false)

const userReview = computed(() => {
    if (!isAuthenticated.value || !user.value) return null
    return reviews.value.find(r => r.user.id === user.value.id) || null
})

const canReview = computed(() => {
    return isAuthenticated.value && props.userStatus === 'platinumed' && !userReview.value
})

function formatDate(dateStr) {
    const date = new Date(dateStr)
    const now = new Date()
    const diffMs = now - date
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffDays === 0) return 'Today'
    if (diffDays === 1) return 'Yesterday'
    if (diffDays < 7) return `${diffDays} days ago`
    if (diffDays < 30) {
        const weeks = Math.floor(diffDays / 7)
        return `${weeks} ${weeks === 1 ? 'week' : 'weeks'} ago`
    }
    if (diffDays < 365) {
        const months = Math.floor(diffDays / 30)
        return `${months} ${months === 1 ? 'month' : 'months'} ago`
    }
    return date.toLocaleDateString()
}

async function fetchReviews() {
    loading.value = true
    try {
        const data = await apiGet(`/games/${props.gameSlug}/reviews`)
        reviews.value = data.reviews
        averageRating.value = data.average_rating
        totalCount.value = data.total_count
    } catch (e) {
        console.error('Failed to fetch reviews:', e)
    } finally {
        loading.value = false
    }
}

function startEdit() {
    if (!userReview.value) return
    formRating.value = userReview.value.rating
    formBody.value = userReview.value.body || ''
    isEditing.value = true
    showForm.value = true
}

function cancelForm() {
    showForm.value = false
    isEditing.value = false
    formRating.value = 0
    formBody.value = ''
    formError.value = null
    hoverRating.value = 0
}

async function submitReview() {
    if (!formRating.value || submitting.value) return

    submitting.value = true
    formError.value = null

    try {
        const payload = {
            rating: formRating.value,
            body: formBody.value || null,
        }

        if (isEditing.value) {
            await apiPut(`/my-games/${props.gameId}/review`, payload)
        } else {
            const response = await apiPost(`/my-games/${props.gameId}/review`, payload)
            if (!response.ok) {
                const data = await response.json()
                throw new Error(data.message || 'Failed to submit review')
            }
        }

        cancelForm()
        await fetchReviews()
    } catch (e) {
        formError.value = e.message
    } finally {
        submitting.value = false
    }
}

async function deleteReview() {
    if (submitting.value) return

    submitting.value = true
    try {
        const response = await apiDelete(`/my-games/${props.gameId}/review`)
        if (!response.ok) {
            throw new Error('Failed to delete review')
        }
        await fetchReviews()
    } catch (e) {
        console.error('Failed to delete review:', e)
    } finally {
        submitting.value = false
    }
}

onMounted(fetchReviews)
</script>
