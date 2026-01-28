<template>
    <div class="space-y-3">
        <!-- User not loaded: show input -->
        <div v-if="!psnUser">
            <!-- My Library button (Admin only) -->
            <template v-if="isAdmin">
                <button
                    @click="loadMyLibrary"
                    :disabled="psnLoading"
                    class="w-full mb-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                >
                    <svg v-if="psnLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>{{ psnLoading ? 'Loading...' : 'Load My Library' }}</span>
                </button>

                <div class="relative flex items-center gap-2 my-2">
                    <div class="flex-1 border-t border-gray-200 dark:border-slate-600"></div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">or search user</span>
                    <div class="flex-1 border-t border-gray-200 dark:border-slate-600"></div>
                </div>
            </template>

            <form @submit.prevent="handleLookup" class="flex gap-2">
                <input
                    v-model="localUsername"
                    type="text"
                    placeholder="Enter PSN username..."
                    class="flex-1 px-3 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-sm dark:text-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 placeholder-gray-400 dark:placeholder-gray-500"
                    :disabled="psnLoading"
                />
                <button
                    type="submit"
                    :disabled="psnLoading || !localUsername.trim()"
                    class="px-4 py-2 bg-gray-100 dark:bg-slate-600 hover:bg-gray-200 dark:hover:bg-slate-500 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
                >
                    <svg v-if="psnLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>{{ psnLoading ? 'Loading...' : 'Load' }}</span>
                </button>
            </form>
            <p v-if="psnError" class="mt-2 text-xs text-red-500 dark:text-red-400">{{ psnError }}</p>
        </div>

        <!-- User loaded: show info -->
        <div v-else>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img
                        v-if="psnUser.avatar"
                        :src="psnUser.avatar"
                        :alt="psnUser.username"
                        class="w-10 h-10 rounded-full border-2 border-gray-200 dark:border-slate-600"
                    />
                    <div v-else class="w-10 h-10 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-medium text-sm text-gray-900 dark:text-white">{{ psnUser.username }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ psnStats.matched_games }} games with guides</div>
                    </div>
                </div>
                <button
                    @click="clearPSN"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                    title="Clear PSN filter"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Stats -->
            <div class="flex gap-4 mt-3 pt-3 border-t border-gray-100 dark:border-slate-700 text-center">
                <div class="flex-1">
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ psnStats.total_psn_games }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Owned</div>
                </div>
                <div class="flex-1">
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ psnStats.matched_games }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Matched</div>
                </div>
                <div class="flex-1">
                    <div class="text-lg font-bold text-green-500">{{ psnStats.has_guide }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">With Guide</div>
                </div>
            </div>

            <!-- Unmatched games link -->
            <div v-if="psnStats.unmatched_games > 0" class="mt-2 text-center">
                <button
                    @click="$emit('show-unmatched')"
                    class="text-xs text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 underline"
                >
                    {{ psnStats.unmatched_games }} unmatched games
                </button>
            </div>

            <!-- PSN Filter Toggles -->
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-700 space-y-3">
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Has guide</span>
                    <div class="relative" @click="toggleHasGuideOnly">
                        <div :class="[
                            'w-9 h-5 rounded-full transition-colors',
                            psnHasGuideOnly ? 'bg-primary-600' : 'bg-gray-200 dark:bg-slate-600'
                        ]"></div>
                        <div :class="[
                            'absolute top-0.5 w-4 h-4 bg-white rounded-full transition-transform shadow-sm',
                            psnHasGuideOnly ? 'left-4' : 'left-0.5'
                        ]"></div>
                    </div>
                </label>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{ psnFilteredCount }} games shown
                </p>
            </div>

            <!-- Save to My List Button -->
            <div v-if="isAuthenticated && psnAllGameIds.length > 0" class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-700">
                <button
                    @click="handleSaveToList"
                    :disabled="savingToList"
                    class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                >
                    <svg v-if="savingToList" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span>{{ savingToList ? 'Saving...' : `Save ${psnStats.matched_games} games to My List` }}</span>
                </button>
                <p v-if="saveResult" :class="['text-xs mt-2 text-center', saveResult.success ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500']">
                    {{ saveResult.message }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { usePSNLibrary } from '../composables/usePSNLibrary'
import { useUserGames } from '../composables/useUserGames'

const emit = defineEmits(['show-unmatched'])

const { bulkAddToList } = useUserGames()
const {
    psnUsername,
    psnLoading,
    psnError,
    psnUser,
    psnStats,
    psnAllGameIds,
    psnHasGuideOnly,
    psnFilteredCount,
    isAdmin,
    isAuthenticated,
    loadMyLibrary,
    lookupPSN,
    toggleHasGuideOnly,
    clearPSN,
} = usePSNLibrary()

const localUsername = ref('')
const savingToList = ref(false)
const saveResult = ref(null)

function handleLookup() {
    lookupPSN(localUsername.value)
}

async function handleSaveToList() {
    if (!isAuthenticated.value || !psnAllGameIds.value.length) return

    savingToList.value = true
    saveResult.value = null

    try {
        const result = await bulkAddToList(psnAllGameIds.value, 'want_to_play')
        saveResult.value = {
            success: true,
            message: `Added ${result.added} games${result.skipped > 0 ? `, ${result.skipped} already in list` : ''}`
        }
    } catch (e) {
        saveResult.value = {
            success: false,
            message: e.message || 'Failed to save games'
        }
    } finally {
        savingToList.value = false
    }
}
</script>
