<template>
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                {{ section ? 'Edit Section' : 'Add Section' }}
            </h2>

            <div class="space-y-5">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="e.g. Easy Adventure Platinums"
                        class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm"
                    />
                </div>

                <!-- Type toggle -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section Type</label>
                    <div class="flex gap-2">
                        <button
                            v-for="t in ['filter', 'manual', 'hybrid']"
                            :key="t"
                            @click="sectionType = t"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors capitalize',
                                sectionType === t
                                    ? 'bg-primary-600 text-white'
                                    : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'
                            ]"
                        >
                            {{ t }}
                        </button>
                    </div>
                </div>

                <!-- Filter definition panel -->
                <div v-if="sectionType === 'filter' || sectionType === 'hybrid'" class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-4 space-y-3">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter Criteria</h3>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Max Difficulty</label>
                            <input v-model.number="form.filter_definition.difficulty_max" type="number" min="1" max="10" placeholder="e.g. 4" class="w-full px-2 py-1.5 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Max Time (hours)</label>
                            <input v-model.number="form.filter_definition.time_max" type="number" min="1" max="200" placeholder="e.g. 15" class="w-full px-2 py-1.5 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Min Difficulty</label>
                            <input v-model.number="form.filter_definition.difficulty_min" type="number" min="1" max="10" placeholder="e.g. 1" class="w-full px-2 py-1.5 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Min Time (hours)</label>
                            <input v-model.number="form.filter_definition.time_min" type="number" min="0" max="200" placeholder="e.g. 40" class="w-full px-2 py-1.5 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Min Critic Score</label>
                            <input v-model.number="form.filter_definition.critic_score_min" type="number" min="0" max="100" placeholder="e.g. 80" class="w-full px-2 py-1.5 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Min User Score</label>
                            <input v-model.number="form.filter_definition.user_score_min" type="number" min="0" max="100" placeholder="e.g. 75" class="w-full px-2 py-1.5 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded text-sm" />
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" v-model="noOnline" class="rounded border-gray-300 dark:border-slate-600" />
                            No online trophies
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" v-model="noMissable" class="rounded border-gray-300 dark:border-slate-600" />
                            No missables
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" v-model="hasPlatinum" class="rounded border-gray-300 dark:border-slate-600" />
                            Has platinum
                        </label>
                    </div>
                </div>

                <!-- Manual games picker -->
                <div v-if="sectionType === 'manual' || sectionType === 'hybrid'">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pinned Games</h3>
                    <div class="relative mb-2">
                        <input
                            v-model="manualSearchQuery"
                            @input="searchManualGames"
                            type="text"
                            placeholder="Search games..."
                            class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm"
                        />
                        <div v-if="manualSearchResults.length > 0" class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg shadow-lg max-h-36 overflow-y-auto">
                            <button
                                v-for="game in manualSearchResults"
                                :key="game.id"
                                @click="addManualGame(game)"
                                class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-slate-600 text-sm"
                            >
                                {{ game.title }} <span class="text-xs text-gray-500">#{{ game.id }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div
                            v-for="(game, idx) in manualGames"
                            :key="game.id"
                            class="flex items-center gap-2 bg-gray-50 dark:bg-slate-700/50 rounded px-3 py-1.5 text-sm"
                        >
                            <span class="flex-1 text-gray-900 dark:text-white">{{ game.title }}</span>
                            <button @click="manualGames.splice(idx, 1)" class="text-red-400 hover:text-red-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Limit slider -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Limit: {{ form.limit }}</label>
                    <input v-model.number="form.limit" type="range" min="3" max="12" class="w-full" />
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-slate-700">
                <button @click="$emit('close')" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white">
                    Cancel
                </button>
                <button @click="save" :disabled="saving || !form.title" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500 text-sm font-medium disabled:opacity-50">
                    {{ saving ? 'Saving...' : (section ? 'Update' : 'Create') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
    pageId: { type: Number, required: true },
    section: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const saving = ref(false)
const sectionType = ref('filter')

const form = ref({
    title: '',
    limit: 6,
    filter_definition: {},
})

// Boolean filter helpers
const noOnline = ref(false)
const noMissable = ref(false)
const hasPlatinum = ref(false)

// Manual games
const manualGames = ref([])
const manualSearchQuery = ref('')
const manualSearchResults = ref([])
let searchTimeout = null

// Sync booleans to/from filter_definition
watch(noOnline, v => {
    if (v) form.value.filter_definition.has_online_trophies = 'false'
    else delete form.value.filter_definition.has_online_trophies
})
watch(noMissable, v => {
    if (v) form.value.filter_definition.missable_trophies = 'false'
    else delete form.value.filter_definition.missable_trophies
})
watch(hasPlatinum, v => {
    if (v) form.value.filter_definition.has_platinum = 'true'
    else delete form.value.filter_definition.has_platinum
})

function searchManualGames() {
    clearTimeout(searchTimeout)
    if (manualSearchQuery.value.length < 2) {
        manualSearchResults.value = []
        return
    }
    searchTimeout = setTimeout(async () => {
        const res = await fetch(`/api/games?search=${encodeURIComponent(manualSearchQuery.value)}&per_page=8`, { credentials: 'include' })
        if (res.ok) {
            const data = await res.json()
            manualSearchResults.value = data.data || []
        }
    }, 300)
}

function addManualGame(game) {
    if (!manualGames.value.some(g => g.id === game.id)) {
        manualGames.value.push(game)
    }
    manualSearchQuery.value = ''
    manualSearchResults.value = []
}

async function save() {
    saving.value = true

    // Build payload
    const payload = {
        title: form.value.title,
        limit: form.value.limit,
    }

    if (sectionType.value === 'filter' || sectionType.value === 'hybrid') {
        // Clean empty values from filter_definition
        const cleaned = {}
        for (const [k, v] of Object.entries(form.value.filter_definition)) {
            if (v !== '' && v !== null && v !== undefined) cleaned[k] = v
        }
        payload.filter_definition = Object.keys(cleaned).length ? cleaned : null
    } else {
        payload.filter_definition = null
    }

    if (sectionType.value === 'manual' || sectionType.value === 'hybrid') {
        payload.game_ids = manualGames.value.map(g => g.id)
        if (payload.game_ids.length === 0) payload.game_ids = null
    } else {
        payload.game_ids = null
    }

    const url = props.section
        ? `/api/admin/directory-pages/${props.pageId}/sections/${props.section.id}`
        : `/api/admin/directory-pages/${props.pageId}/sections`

    const method = props.section ? 'PUT' : 'POST'

    const res = await fetch(url, {
        method,
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload),
    })

    saving.value = false
    if (res.ok) emit('saved')
}

// Initialize from existing section
onMounted(async () => {
    if (props.section) {
        form.value.title = props.section.title
        form.value.limit = props.section.limit || 6
        form.value.filter_definition = { ...(props.section.filter_definition || {}) }

        noOnline.value = form.value.filter_definition.has_online_trophies === 'false'
        noMissable.value = form.value.filter_definition.missable_trophies === 'false'
        hasPlatinum.value = form.value.filter_definition.has_platinum === 'true'

        if (props.section.game_ids?.length && props.section.filter_definition) {
            sectionType.value = 'hybrid'
        } else if (props.section.game_ids?.length) {
            sectionType.value = 'manual'
        } else {
            sectionType.value = 'filter'
        }

        // Load manual game details
        if (props.section.game_ids?.length) {
            for (const id of props.section.game_ids) {
                const res = await fetch(`/api/games/${id}`, { credentials: 'include' })
                if (res.ok) {
                    const game = await res.json()
                    manualGames.value.push(game)
                }
            }
        }
    }
})
</script>
