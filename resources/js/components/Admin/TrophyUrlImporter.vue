<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-4">
            <!-- Header + Page Selector (compact) -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Trophy URL Importer</h1>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-primary-600 text-white px-3 py-2 rounded-lg">
                        <span class="text-sm opacity-80">Page</span>
                        <button @click="currentPage = Math.max(1, currentPage - 1)" class="p-1 rounded hover:bg-white/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <input type="number" v-model.number="currentPage" min="1" :max="maxPages" class="w-16 text-center text-gray-900 rounded px-1 py-1 text-sm font-bold">
                        <button @click="currentPage = Math.min(maxPages, currentPage + 1)" class="p-1 rounded hover:bg-white/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <span class="text-sm opacity-60">/ {{ maxPages }}</span>
                    </div>
                    <div class="flex gap-2 text-sm">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" v-model="source" value="psnprofiles" class="mr-1">
                            <span :class="source === 'psnprofiles' ? 'font-semibold' : ''">PSN</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" v-model="source" value="playstationtrophies" class="mr-1">
                            <span :class="source === 'playstationtrophies' ? 'font-semibold' : ''">PST</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Copy URL helper -->
            <div class="bg-gray-100 dark:bg-slate-700 rounded-lg p-2 flex items-center gap-3">
                <code class="flex-1 text-xs font-mono text-gray-600 dark:text-gray-400 select-all truncate">{{ viewSourceUrl }}</code>
                <button
                    @click="copyUrl"
                    class="px-3 py-1 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 text-sm rounded border dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700/50 flex items-center gap-1 shrink-0"
                >
                    <svg v-if="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <svg v-else class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ copied ? 'Copied!' : 'Copy' }}
                </button>
            </div>

            <!-- PASTE AREA (prominent) -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                <textarea
                    ref="textareaRef"
                    v-model="html"
                    rows="10"
                    class="w-full border-2 border-dashed border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-300 dark:placeholder-gray-500 rounded-lg focus:border-primary-500 focus:ring-0 font-mono text-xs p-3"
                    :class="{ 'border-primary-400 bg-primary-50': loading }"
                    :placeholder="'Paste page ' + currentPage + ' source here (Ctrl+V)...\n\nAuto-imports on paste!'"
                    @paste="onPaste"
                ></textarea>
                <div class="flex items-center justify-between mt-2">
                    <div class="flex items-center gap-3 text-sm">
                        <label class="flex items-center text-gray-600 dark:text-gray-400">
                            <input type="checkbox" v-model="autoImport" class="mr-1">
                            Auto-import
                        </label>
                        <span v-if="htmlLength" class="text-gray-400 dark:text-gray-500">({{ htmlLength }} chars)</span>
                        <span v-if="loading" class="text-primary-600 flex items-center gap-1">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importing...
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="lastResult" :class="lastResult.success ? 'text-green-600' : 'text-red-600'" class="text-sm">
                            {{ lastResult.message }}
                        </span>
                        <button
                            v-if="!autoImport"
                            @click="importUrls"
                            :disabled="!html || loading"
                            class="px-4 py-1.5 bg-primary-600 text-white text-sm rounded hover:bg-primary-700 disabled:opacity-50"
                        >
                            Import
                        </button>
                        <button
                            @click="html = ''"
                            :disabled="!html"
                            class="px-3 py-1.5 text-gray-500 dark:text-gray-400 text-sm hover:text-gray-700 dark:hover:text-gray-300 disabled:opacity-50"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- PowerPyx Import -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-3 flex items-center justify-between">
                <div>
                    <span class="font-medium text-gray-900 dark:text-white text-sm">PowerPyx Guides</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">Auto-scrape from powerpyx.com/guides</span>
                </div>
                <div class="flex items-center gap-3">
                    <span v-if="ppxResult" :class="ppxResult.success ? 'text-green-600' : 'text-red-600'" class="text-sm">
                        {{ ppxResult.message }}
                    </span>
                    <button
                        @click="scrapePowerPyx"
                        :disabled="ppxLoading"
                        class="px-4 py-1.5 bg-orange-600 text-white text-sm rounded hover:bg-orange-700 disabled:opacity-50 flex items-center gap-2"
                    >
                        <svg v-if="ppxLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ ppxLoading ? 'Importing...' : 'Import PowerPyx' }}
                    </button>
                </div>
            </div>

            <!-- Stats (compact) -->
            <div v-if="stats" class="grid grid-cols-5 gap-2 text-center">
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-3">
                    <div class="text-xl font-bold text-blue-600">{{ stats.psnprofiles?.total_urls || 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">PSN URLs</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-3">
                    <div class="text-xl font-bold text-green-600">{{ stats.psnprofiles?.matched || 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Matched</div>
                </div>
                <router-link to="/admin/trophy-urls/unmatched" class="bg-white dark:bg-slate-800 rounded-lg shadow p-3 hover:bg-red-50 dark:hover:bg-slate-700">
                    <div class="text-xl font-bold text-red-600">{{ (stats.psnprofiles?.unmatched || 0) + (stats.playstationtrophies?.unmatched || 0) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Unmatched</div>
                </router-link>
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-3">
                    <div class="text-xl font-bold text-purple-600">{{ stats.playstationtrophies?.total_urls || 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">PST URLs</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-3">
                    <div class="text-xl font-bold text-gray-600 dark:text-gray-400">{{ stats.total_games || 0 }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Games</div>
                </div>
            </div>

            <!-- Import Log (compact) -->
            <div v-if="importLog.length > 0" class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-medium text-gray-900 dark:text-white text-sm">Import Log</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ importLog.length }} pages</span>
                </div>
                <div class="space-y-0.5 max-h-32 overflow-y-auto font-mono text-xs">
                    <div
                        v-for="(log, index) in importLog"
                        :key="index"
                        class="flex items-center gap-2 py-0.5"
                    >
                        <span :class="log.success ? 'text-green-500' : 'text-red-500'">{{ log.success ? '✓' : '✗' }}</span>
                        <span class="text-gray-400 dark:text-gray-500">{{ log.time }}</span>
                        <span class="text-gray-700 dark:text-gray-300 flex-1">{{ log.message }}</span>
                        <span v-if="log.stats?.matched > 0" class="text-green-600">+{{ log.stats.matched }}</span>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex gap-4 text-sm">
                <router-link to="/admin/games" class="text-primary-600 hover:text-primary-800">← Games</router-link>
                <router-link to="/admin/trophy-urls/unmatched" class="text-primary-600 hover:text-primary-800">Unmatched URLs →</router-link>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from './AdminLayout.vue'

const html = ref('')
const source = ref('psnprofiles')
const loading = ref(false)
const stats = ref(null)
const lastResult = ref(null)
const importLog = ref([])
const currentPage = ref(1)
const autoImport = ref(true)
const textareaRef = ref(null)
const copied = ref(false)
const ppxLoading = ref(false)
const ppxResult = ref(null)

const maxPages = computed(() => source.value === 'psnprofiles' ? 304 : 100)

const htmlLength = computed(() => html.value.length.toLocaleString())

const viewSourceUrl = computed(() => {
    if (source.value === 'psnprofiles') {
        return `view-source:https://psnprofiles.com/guides?page=${currentPage.value}`
    }
    return `view-source:https://www.playstationtrophies.org/guides?page=${currentPage.value}`
})

async function copyUrl() {
    await navigator.clipboard.writeText(viewSourceUrl.value)
    copied.value = true
    setTimeout(() => copied.value = false, 1500)
}

async function loadStats() {
    try {
        const response = await fetch('/api/admin/trophy-urls/stats')
        stats.value = await response.json()
    } catch (e) {
        console.error('Failed to load stats:', e)
    }
}

function onPaste() {
    setTimeout(async () => {
        // Auto-detect source from pasted content
        if (html.value.includes('psnprofiles.com')) {
            source.value = 'psnprofiles'
        } else if (html.value.includes('playstationtrophies.org')) {
            source.value = 'playstationtrophies'
        }

        // Auto-import if enabled
        if (autoImport.value && html.value.length > 100) {
            await importUrls(true) // pass flag for auto-import mode

            // On any success, advance to next page
            if (lastResult.value?.success) {
                currentPage.value = Math.min(maxPages.value, currentPage.value + 1)
                html.value = ''
            }

            // Focus textarea for next paste
            textareaRef.value?.focus()
        }
    }, 100)
}

async function importUrls(isAutoImport = false) {
    if (!html.value || loading.value) return

    loading.value = true
    lastResult.value = null

    try {
        const response = await fetch('/api/admin/trophy-urls/import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                html: html.value,
                source: source.value,
            }),
        })

        const result = await response.json()
        lastResult.value = result

        // Add to log with page number
        importLog.value.unshift({
            time: new Date().toLocaleTimeString(),
            page: currentPage.value,
            success: result.success,
            message: `Page ${currentPage.value}: ${result.stats?.found || 0} found, ${result.stats?.new || 0} new`,
            stats: result.stats,
        })

        // Clear input on manual success
        if (!isAutoImport && result.success) {
            html.value = ''
        }

        // Refresh stats
        await loadStats()

    } catch (e) {
        lastResult.value = {
            success: false,
            message: 'Error: ' + e.message,
        }
    } finally {
        loading.value = false
    }
}

async function scrapePowerPyx() {
    if (ppxLoading.value) return

    ppxLoading.value = true
    ppxResult.value = null

    try {
        const response = await fetch('/api/admin/trophy-urls/scrape-powerpyx', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
        })

        const result = await response.json()
        ppxResult.value = result

        if (result.success) {
            importLog.value.unshift({
                time: new Date().toLocaleTimeString(),
                success: true,
                message: `PPX: ${result.stats?.new || 0} new, ${result.stats?.matched || 0} matched`,
                stats: result.stats,
            })
            await loadStats()
        }
    } catch (e) {
        ppxResult.value = {
            success: false,
            message: 'Error: ' + e.message,
        }
    } finally {
        ppxLoading.value = false
    }
}

onMounted(() => {
    loadStats()
})
</script>
