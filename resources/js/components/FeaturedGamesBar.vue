<template>
    <div v-if="games.length > 0" class="mb-3">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-3">
            <!-- Header -->
            <div class="flex items-center gap-2 mb-2.5">
                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wide">{{ displayLabel }}</span>
            </div>

            <!-- Games Row -->
            <div class="flex gap-3 overflow-x-auto scrollbar-hide">
                <a
                    v-for="item in games"
                    :key="item.id"
                    :href="'/game/' + item.game.slug"
                    class="flex items-center gap-3 flex-shrink-0 rounded-lg p-2 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors group min-w-[260px] sm:min-w-0 sm:flex-1"
                >
                    <!-- Cover -->
                    <img
                        v-if="item.game.cover_url"
                        :src="item.game.cover_url"
                        :alt="item.game.title"
                        class="w-12 h-16 rounded object-cover shrink-0 ring-1 ring-gray-200 dark:ring-slate-600"
                    />
                    <div v-else class="w-12 h-16 rounded bg-gray-200 dark:bg-slate-700 shrink-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>

                    <!-- Info -->
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                            {{ item.game.title }}
                        </div>
                        <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <span v-if="item.game.difficulty" class="font-medium">{{ item.game.difficulty }}/10</span>
                            <span v-if="item.game.difficulty && item.game.time_range" class="text-gray-300 dark:text-gray-600">&middot;</span>
                            <span v-if="item.game.time_range">{{ item.game.time_range }}</span>
                        </div>
                        <!-- Guide Badges -->
                        <div v-if="item.game.has_psnprofiles || item.game.has_playstationtrophies || item.game.has_powerpyx" class="flex items-center gap-1 mt-1.5">
                            <span v-if="item.game.has_psnprofiles" class="guide-badge-psnp px-1.5 py-0.5 rounded text-[10px] font-bold">PSNP</span>
                            <span v-if="item.game.has_playstationtrophies" class="guide-badge-pst px-1.5 py-0.5 rounded text-[10px] font-bold">PST</span>
                            <span v-if="item.game.has_powerpyx" class="guide-badge-ppx px-1.5 py-0.5 rounded text-[10px] font-bold">PPX</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const games = ref([])

const displayLabel = computed(() => {
    if (games.value.length > 0) {
        return games.value[0].label || 'Indie Spotlight'
    }
    return 'Indie Spotlight'
})

async function fetchFeaturedGames() {
    try {
        const response = await fetch('/api/featured-games')
        if (!response.ok) return
        const data = await response.json()
        games.value = data
    } catch (e) {
        // Silently fail — section just won't render
    }
}

onMounted(() => {
    fetchFeaturedGames()
})
</script>
