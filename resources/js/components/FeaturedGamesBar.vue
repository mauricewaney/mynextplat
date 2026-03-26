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
                    @click="trackClick(item)"
                    class="flex items-center gap-3 flex-shrink-0 rounded-lg p-2 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors group min-w-[260px] sm:min-w-0 sm:flex-1"
                >
                    <!-- Cover -->
                    <img
                        :src="item.game.cover_url || '/images/fallback-cover.svg'"
                        :alt="item.game.title"
                        width="48"
                        height="64"
                        class="w-12 h-16 rounded object-cover shrink-0 ring-1 ring-gray-200 dark:ring-slate-600"
                        @error="$event.target.src = '/images/fallback-cover.svg'"
                    />

                    <!-- Info -->
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                            {{ item.game.title }}
                        </div>
                        <!-- Price / Trophy Badges -->
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            <span v-if="priceLabel(item.game)" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                {{ priceLabel(item.game) }}
                            </span>
                            <span v-if="item.game.has_platinum != null" class="inline-flex items-center gap-0.5 text-[10px] font-bold text-blue-300 dark:text-blue-200">
                                <TrophyIcon tier="platinum" />
                                {{ item.game.has_platinum ? 1 : 0 }}
                            </span>
                            <template v-if="item.game.total_trophies">
                                <span v-if="item.game.gold_count" class="inline-flex items-center gap-0.5 text-[10px] font-bold text-yellow-500 dark:text-yellow-400">
                                    <TrophyIcon tier="gold" />
                                    {{ item.game.gold_count }}
                                </span>
                                <span v-if="item.game.silver_count" class="inline-flex items-center gap-0.5 text-[10px] font-bold text-gray-400 dark:text-gray-300">
                                    <TrophyIcon tier="silver" />
                                    {{ item.game.silver_count }}
                                </span>
                                <span v-if="item.game.bronze_count" class="inline-flex items-center gap-0.5 text-[10px] font-bold text-amber-700 dark:text-amber-500">
                                    <TrophyIcon tier="bronze" />
                                    {{ item.game.bronze_count }}
                                </span>
                            </template>
                        </div>
                        <!-- Tagline -->
                        <div v-if="item.tagline" class="text-[11px] italic text-gray-400 dark:text-gray-500 truncate mt-1">
                            {{ item.tagline }}
                        </div>
                        <!-- PSN Store Link -->
                        <a
                            v-if="item.game.psn_store_url"
                            :href="item.game.psn_store_url"
                            target="_blank"
                            rel="noopener"
                            @click.stop
                            class="inline-flex items-center gap-1 mt-1 text-[10px] font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                        >
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M21.58 16.09l-1.09-7.66C20.21 6.46 18.52 5 16.53 5H7.47C5.48 5 3.79 6.46 3.51 8.43l-1.09 7.66C2.2 17.63 3.39 19 4.94 19h0c.68 0 1.32-.27 1.8-.75L9 16h6l2.25 2.25c.48.48 1.13.75 1.8.75h0c1.55 0 2.74-1.37 2.53-2.91zM11 11H9v2H8v-2H6v-1h2V8h1v2h2v1zm4 2c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm2-3c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/></svg>
                            PS Store
                        </a>
                    </div>
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { apiPost } from '../utils/api'
import TrophyIcon from './TrophyIcon.vue'

const games = ref([])
const loading = ref(true)
const displayLabel = ref('Featured')

function priceLabel(game) {
    if (game.is_psplus_extra || game.is_psplus_premium) {
        return 'Free w/ PS Plus'
    }
    if (game.current_discount_price != null) {
        return `$${Number(game.current_discount_price).toFixed(2)}`
    }
    if (game.base_price != null) {
        return `$${Number(game.base_price).toFixed(2)}`
    }
    return null
}

function trackClick(item) {
    apiPost('/featured-clicks', {
        placement_id: item.id,
        game_id: item.game.id,
    }).catch(() => {})
}

async function fetchFeaturedGames() {
    try {
        const response = await fetch('/api/featured-games')
        if (!response.ok) return
        const data = await response.json()
        displayLabel.value = data.label || 'Featured'
        games.value = data.games || []
    } catch (e) {
        // Silently fail — section just won't render
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchFeaturedGames()
})
</script>
