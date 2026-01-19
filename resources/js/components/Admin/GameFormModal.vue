<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

            <!-- Modal -->
            <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ isEdit ? 'Edit Game' : 'Add New Game' }}
                    </h2>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Basic Information</h3>

                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Game Title <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., Ghost of Tsushima"
                            />
                            <p v-if="errors.title" class="text-red-500 text-sm mt-1">{{ errors.title[0] }}</p>
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Slug (URL-friendly, auto-generated if empty)
                            </label>
                            <input
                                v-model="form.slug"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="ghost-of-tsushima"
                            />
                            <p v-if="errors.slug" class="text-red-500 text-sm mt-1">{{ errors.slug[0] }}</p>
                        </div>

                        <!-- Developer & Publisher -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Developer</label>
                                <input
                                    v-model="form.developer"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Sucker Punch Productions"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                                <input
                                    v-model="form.publisher"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Sony Interactive Entertainment"
                                />
                            </div>
                        </div>

                        <!-- Release Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Release Date</label>
                            <input
                                v-model="form.release_date"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Trophy Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Trophy Information</h3>

                        <!-- Difficulty -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Difficulty (1-10)
                            </label>
                            <input
                                v-model.number="form.difficulty"
                                type="number"
                                min="1"
                                max="10"
                                step="1"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="5"
                            />
                            <p class="text-xs text-gray-500 mt-1">1 = Very Easy, 10 = Extremely Hard</p>
                        </div>

                        <!-- Time Range -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Min Time (hours)</label>
                                <input
                                    v-model.number="form.time_min"
                                    type="number"
                                    min="0"
                                    step="0.5"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="50"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max Time (hours)</label>
                                <input
                                    v-model.number="form.time_max"
                                    type="number"
                                    min="0"
                                    step="0.5"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="60"
                                />
                            </div>
                        </div>

                        <!-- Playthroughs Required -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Playthroughs Required</label>
                            <input
                                v-model.number="form.playthroughs_required"
                                type="number"
                                min="1"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="1"
                            />
                        </div>

                        <!-- Trophy Booleans -->
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    v-model="form.has_online_trophies"
                                    type="checkbox"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                />
                                <span class="text-sm font-medium text-gray-700">Has Online Trophies</span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    v-model="form.missable_trophies"
                                    type="checkbox"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                />
                                <span class="text-sm font-medium text-gray-700">Has Missable Trophies</span>
                            </label>
                        </div>
                    </div>

                    <!-- Scores & Reviews -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Scores & Reviews</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Metacritic Score (0-100)</label>
                                <input
                                    v-model.number="form.metacritic_score"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="85"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">OpenCritic Score (0-100)</label>
                                <input
                                    v-model.number="form.opencritic_score"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="88"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Pricing (Optional)</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Price (€)</label>
                                <input
                                    v-model.number="form.price_current"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="69.99"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Original Price (€)</label>
                                <input
                                    v-model.number="form.price_original"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="79.99"
                                />
                            </div>
                        </div>

                        <!-- Affiliate Links -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amazon Link</label>
                                <input
                                    v-model="form.amazon_link"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://amazon.com/..."
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bol.com Link</label>
                                <input
                                    v-model="form.bol_link"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://bol.com/..."
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Categories</h3>

                        <!-- Genres -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Genres</label>
                            <div class="border border-gray-300 rounded-md p-3 max-h-48 overflow-y-auto">
                                <label
                                    v-for="genre in genres"
                                    :key="genre.id"
                                    class="flex items-center space-x-2 py-1 hover:bg-gray-50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="genre.id"
                                        v-model="form.genre_ids"
                                        class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    />
                                    <span class="text-sm">{{ genre.name }}</span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Selected: {{ form.genre_ids.length }}</p>
                        </div>

                        <!-- Tags -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                            <div class="border border-gray-300 rounded-md p-3 max-h-48 overflow-y-auto">
                                <label
                                    v-for="tag in tags"
                                    :key="tag.id"
                                    class="flex items-center space-x-2 py-1 hover:bg-gray-50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="tag.id"
                                        v-model="form.tag_ids"
                                        class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    />
                                    <span class="text-sm">{{ tag.name }}</span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Selected: {{ form.tag_ids.length }}</p>
                        </div>

                        <!-- Platforms -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Platforms <span class="text-red-500">*</span>
                            </label>
                            <div class="border border-gray-300 rounded-md p-3">
                                <label
                                    v-for="platform in platforms"
                                    :key="platform.id"
                                    class="flex items-center space-x-2 py-1 hover:bg-gray-50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="platform.id"
                                        v-model="form.platform_ids"
                                        class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    />
                                    <span class="text-sm font-medium">{{ platform.name }}</span>
                                </label>
                            </div>
                            <p v-if="errors.platform_ids" class="text-red-500 text-sm mt-1">{{ errors.platform_ids[0] }}</p>
                            <p v-else class="text-xs text-gray-500 mt-1">At least one platform required. Selected: {{ form.platform_ids.length }}</p>
                        </div>
                    </div>

                    <!-- Images & Links -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Images & External Links</h3>

                        <!-- Image URLs -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cover Image URL</label>
                                <input
                                    v-model="form.cover_url"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://..."
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Banner Image URL</label>
                                <input
                                    v-model="form.banner_url"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://..."
                                />
                            </div>
                        </div>

                        <!-- External Links -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PSNProfiles URL</label>
                                <input
                                    v-model="form.psnprofiles_url"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://psnprofiles.com/..."
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PSN Store URL</label>
                                <input
                                    v-model="form.psn_store_url"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://store.playstation.com/..."
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Game description..."
                        ></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                            :disabled="loading"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="loading"
                        >
                            <span v-if="loading">Saving...</span>
                            <span v-else>{{ isEdit ? 'Update Game' : 'Create Game' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    game: {
        type: Object,
        default: null
    },
    genres: {
        type: Array,
        required: true
    },
    tags: {
        type: Array,
        required: true
    },
    platforms: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const errors = ref({});

// Form data
const form = ref({
    title: '',
    slug: '',
    description: '',
    developer: '',
    publisher: '',
    release_date: '',
    difficulty: null,
    time_min: null,
    time_max: null,
    playthroughs_required: 1,
    has_online_trophies: false,
    missable_trophies: false,
    metacritic_score: null,
    opencritic_score: null,
    price_current: null,
    price_original: null,
    amazon_link: '',
    bol_link: '',
    cover_url: '',
    banner_url: '',
    psnprofiles_url: '',
    psn_store_url: '',
    genre_ids: [],
    tag_ids: [],
    platform_ids: []
});

const isEdit = computed(() => !!props.game);

// Format date for HTML date input (YYYY-MM-DD)
function formatDateForInput(dateString) {
    if (!dateString) return ''
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return ''
    return date.toISOString().split('T')[0]
}

// Watch for game prop changes (when editing)
watch(() => props.game, (game) => {
    if (game) {
        form.value = {
            title: game.title || '',
            slug: game.slug || '',
            description: game.description || '',
            developer: game.developer || '',
            publisher: game.publisher || '',
            release_date: formatDateForInput(game.release_date),
            difficulty: game.difficulty || null,
            time_min: game.time_min || null,
            time_max: game.time_max || null,
            playthroughs_required: game.playthroughs_required || 1,
            has_online_trophies: game.has_online_trophies || false,
            missable_trophies: game.missable_trophies || false,
            metacritic_score: game.metacritic_score || null,
            opencritic_score: game.opencritic_score || null,
            price_current: game.price_current || null,
            price_original: game.price_original || null,
            amazon_link: game.amazon_link || '',
            bol_link: game.bol_link || '',
            cover_url: game.cover_url || '',
            banner_url: game.banner_url || '',
            psnprofiles_url: game.psnprofiles_url || '',
            psn_store_url: game.psn_store_url || '',
            genre_ids: game.genres?.map(g => g.id) || [],
            tag_ids: game.tags?.map(t => t.id) || [],
            platform_ids: game.platforms?.map(p => p.id) || []
        };
    }
}, { immediate: true });

// Reset form when modal closes
watch(() => props.show, (show) => {
    if (!show && !props.game) {
        resetForm();
    }
    errors.value = {};
});

function resetForm() {
    form.value = {
        title: '',
        slug: '',
        description: '',
        developer: '',
        publisher: '',
        release_date: '',
        difficulty: null,
        time_min: null,
        time_max: null,
        playthroughs_required: 1,
        has_online_trophies: false,
        missable_trophies: false,
        metacritic_score: null,
        opencritic_score: null,
        price_current: null,
        price_original: null,
        amazon_link: '',
        bol_link: '',
        cover_url: '',
        banner_url: '',
        psnprofiles_url: '',
        psn_store_url: '',
        genre_ids: [],
        tag_ids: [],
        platform_ids: []
    };
}

async function submitForm() {
    loading.value = true;
    errors.value = {};

    try {
        if (isEdit.value) {
            // Update existing game
            const response = await axios.put(`/api/admin/games/${props.game.id}`, form.value);
            emit('saved', response.data);
        } else {
            // Create new game
            const response = await axios.post('/api/admin/games', form.value);
            emit('saved', response.data);
        }

        emit('close');
        resetForm();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            // Validation errors
            errors.value = error.response.data.errors;
        } else {
            alert('An error occurred while saving the game. Please try again.');
            console.error('Error saving game:', error);
        }
    } finally {
        loading.value = false;
    }
}

function closeModal() {
    if (!loading.value) {
        emit('close');
    }
}
</script>
