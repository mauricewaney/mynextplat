<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">NP ID Management</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Link PSN NP Communication IDs to games for reliable matching
                    </p>
                </div>
                <button
                    @click="loadData"
                    :disabled="loading"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors dark:hover:bg-primary-500"
                >
                    <span v-if="loading">Loading...</span>
                    <span v-else>Refresh</span>
                </button>
            </div>

            <!-- Collect from PSN User -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Collect NP IDs from PSN User</h3>
                <div class="flex gap-2">
                    <input
                        v-model="collectUsername"
                        @keyup.enter="collectFromUser"
                        type="text"
                        placeholder="Enter PSN username..."
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        :disabled="collecting"
                    />
                    <button
                        @click="collectFromUser"
                        :disabled="collecting || !collectUsername.trim()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors whitespace-nowrap"
                    >
                        <span v-if="collecting">Collecting...</span>
                        <span v-else>Collect NP IDs</span>
                    </button>
                    <button
                        @click="autoMatchAll"
                        :disabled="autoMatching || psnStoreMatching"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors whitespace-nowrap"
                    >
                        <span v-if="autoMatching">Matching...</span>
                        <span v-else>Auto-match 100%</span>
                    </button>
                    <button
                        @click="autoMatchPsnStore"
                        :disabled="psnStoreMatching || autoMatching || altNamesMatching"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors whitespace-nowrap"
                        title="Search PSN Store for games without NPWR IDs and match them to collected trophy lists"
                    >
                        <span v-if="psnStoreMatching">Searching PSN...</span>
                        <span v-else>Auto-match via PSN Store</span>
                    </button>
                    <button
                        @click="autoMatchAltNames"
                        :disabled="altNamesMatching || autoMatching || psnStoreMatching"
                        class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-50 transition-colors whitespace-nowrap"
                        title="Use IGDB alternative names to find PSN Store matches for remaining games"
                    >
                        <span v-if="altNamesMatching">IGDB Alt Names...</span>
                        <span v-else>Auto-match via Alt Names</span>
                    </button>
                </div>
                <p v-if="collectResult" class="mt-2 text-sm" :class="collectResult.success ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ collectResult.message }}
                </p>
                <div v-if="collectResult && !collectResult.success && collectResult.suggestions?.length" class="mt-1 flex items-center gap-1 flex-wrap">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Try:</span>
                    <button
                        v-for="s in collectResult.suggestions"
                        :key="s"
                        @click="collectUsername = s; collectFromUser()"
                        class="text-xs px-2 py-0.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded hover:bg-primary-100 dark:hover:bg-primary-900/50 transition-colors"
                    >{{ s }}</button>
                </div>
            </div>

            <!-- Mass NP ID Collector -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                <button
                    @click="massShowSection = !massShowSection"
                    class="flex items-center justify-between w-full text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    <span>Mass Collect from PSNProfiles Leaderboard</span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': massShowSection }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div v-if="massShowSection" class="mt-3 space-y-3">
                    <!-- Step 1: Paste HTML -->
                    <textarea
                        v-model="massHtml"
                        placeholder="Paste HTML source from a PSNProfiles leaderboard page..."
                        rows="4"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent font-mono"
                        :disabled="massCollecting"
                    />
                    <button
                        @click="parseMassHtml"
                        :disabled="massParsing || !massHtml.trim() || massCollecting"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors text-sm"
                    >
                        {{ massParsing ? 'Parsing...' : 'Parse Usernames' }}
                    </button>

                    <!-- Step 2: Username list -->
                    <div v-if="massUsernames.length" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ massUsernames.filter(u => u.selected).length }} / {{ massUsernames.length }} users selected
                            </span>
                            <button
                                @click="massToggleAll"
                                class="text-xs text-primary-600 hover:text-primary-700 underline"
                                :disabled="massCollecting"
                            >
                                {{ massUsernames.every(u => u.selected) ? 'Deselect All' : 'Select All' }}
                            </button>
                        </div>
                        <div class="max-h-40 overflow-y-auto border border-gray-200 dark:border-slate-600 rounded-lg p-2 space-y-1">
                            <label
                                v-for="u in massUsernames"
                                :key="u.username"
                                class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 px-2 py-1 rounded cursor-pointer"
                            >
                                <input type="checkbox" v-model="u.selected" :disabled="massCollecting" class="rounded border-gray-300 text-purple-600" />
                                <span :class="{ 'font-medium text-green-600 dark:text-green-400': u.status === 'success', 'text-red-500': u.status === 'error', 'text-yellow-600 dark:text-yellow-400 animate-pulse': u.status === 'processing' }">
                                    {{ u.username }}
                                </span>
                                <span v-if="u.message" class="text-xs text-gray-400 dark:text-gray-500 ml-auto truncate max-w-[300px]">{{ u.message }}</span>
                            </label>
                        </div>
                        <button
                            @click="startMassCollection"
                            :disabled="massCollecting || !massUsernames.some(u => u.selected)"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors text-sm"
                        >
                            {{ massCollecting ? 'Collecting...' : `Start Collection (${massUsernames.filter(u => u.selected).length} users)` }}
                        </button>
                    </div>

                    <!-- Step 3: Progress -->
                    <div v-if="massCollecting || massResults.length" class="space-y-2">
                        <div v-if="massCollecting" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 animate-spin text-purple-600" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
                                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" class="opacity-75" />
                            </svg>
                            <span>Processing <strong>{{ massCurrentUser }}</strong> ({{ massCurrentIndex + 1 }} / {{ massTotalUsers }})</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                            <div
                                class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                                :style="{ width: (massTotalUsers > 0 ? (massResults.length / massTotalUsers) * 100 : 0) + '%' }"
                            />
                        </div>
                    </div>

                    <!-- Summary -->
                    <div v-if="!massCollecting && massResults.length" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 text-sm text-green-700 dark:text-green-400">
                        Done: {{ massResults.filter(r => r.success).length }}/{{ massResults.length }} users collected.
                        {{ massTotalNew }} new titles, {{ massTotalAutoMatched }} auto-matched.
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div v-if="stats" class="grid grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_titles }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total PSN titles</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-green-600">{{ stats.matched_titles }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Matched</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-orange-600">{{ stats.unmatched_titles }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Unmatched</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-primary-600">
                        {{ stats.total_titles > 0 ? Math.round((stats.matched_titles / stats.total_titles) * 100) : 0 }}%
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Match rate</div>
                </div>
            </div>

            <!-- Skipped indicator -->
            <div v-if="skipCount > 0" class="bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg px-4 py-2 flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ skipCount }} item{{ skipCount === 1 ? '' : 's' }} skipped (shown at bottom)
                </span>
                <button
                    @click="clearSkipped()"
                    class="text-sm text-primary-600 hover:text-primary-700 underline"
                >
                    Clear all skips
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="searchQuery"
                            @input="debouncedLoad"
                            type="text"
                            placeholder="Search PSN titles..."
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                    </div>
                    <select
                        v-model="selectedPlatform"
                        @change="loadUnmatched"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="">All Platforms</option>
                        <option value="PS5">PS5</option>
                        <option value="PS4">PS4</option>
                        <option value="PS3">PS3</option>
                        <option value="PSVITA">PS Vita</option>
                    </select>
                    <select
                        v-model="sortBy"
                        @change="loadUnmatched"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="similarity">Best Match</option>
                        <option value="times_seen">Most Popular</option>
                        <option value="title">Title A-Z</option>
                        <option value="created_at">Recently Added</option>
                    </select>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!loading && unmatched.length === 0" class="bg-white dark:bg-slate-800 rounded-lg shadow p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No unmatched titles</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Use the input above to collect NP IDs from PSN users
                </p>
            </div>

            <!-- Unmatched List -->
            <div v-else class="space-y-4">
                <div
                    v-for="item in unmatched"
                    :key="item.id"
                    class="rounded-lg shadow p-4 transition-all"
                    :class="item.is_skipped ? 'bg-gray-100 dark:bg-slate-800/50 opacity-60' : 'bg-white dark:bg-slate-800'"
                >
                    <div class="flex items-start gap-4">
                        <!-- Skip/Unskip button -->
                        <button
                            v-if="item.is_skipped"
                            @click="unskipItem(item)"
                            class="text-xs text-primary-500 hover:text-primary-700 px-2 py-1 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded transition-colors shrink-0"
                            title="Move back to top"
                        >
                            Unskip
                        </button>
                        <button
                            v-else
                            @click="skipItem(item)"
                            class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 px-2 py-1 hover:bg-gray-100 dark:hover:bg-slate-700 rounded transition-colors shrink-0"
                            title="Move to bottom of list"
                        >
                            Skip
                        </button>

                        <!-- Icon -->
                        <img
                            v-if="item.icon_url"
                            :src="item.icon_url"
                            class="w-24 h-24 rounded object-cover bg-gray-100"
                            @error="$event.target.style.display = 'none'"
                        />
                        <div v-else class="w-24 h-24 rounded bg-gray-100 dark:bg-slate-700 flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>

                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium text-gray-900 dark:text-white">{{ item.psn_title || '(Empty title)' }}</span>
                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 text-xs font-mono rounded">
                                    {{ item.np_communication_id }}
                                </span>
                                <span v-if="item.platform" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs rounded">
                                    {{ item.platform }}
                                </span>
                            </div>

                            <!-- Trophy info -->
                            <div v-if="item.has_platinum || item.bronze_count" class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span v-if="item.has_platinum" class="text-yellow-600">Platinum</span>
                                <span v-if="item.gold_count">{{ item.gold_count }} Gold</span>
                                <span v-if="item.silver_count">{{ item.silver_count }} Silver</span>
                                <span v-if="item.bronze_count">{{ item.bronze_count }} Bronze</span>
                            </div>

                            <!-- Discovered from -->
                            <div v-if="item.discovered_from" class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                First seen: {{ item.discovered_from }}
                            </div>

                            <!-- Search & Link -->
                            <div class="mt-3 space-y-3">
                                <!-- Auto Suggestions (preloaded) -->
                                <div v-if="item.suggestions?.length" class="mb-3">
                                    <div class="text-xs font-medium text-green-600 dark:text-green-400 mb-2">Suggested matches:</div>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="suggestion in item.suggestions"
                                            :key="suggestion.id"
                                            @click="linkToGame(item, suggestion.id)"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm rounded transition-colors border"
                                            :class="suggestion.similarity >= 80
                                                ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100'
                                                : suggestion.similarity >= 60
                                                    ? 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100'
                                                    : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'"
                                        >
                                            <img v-if="suggestion.cover_url" :src="suggestion.cover_url" class="w-10 h-14 rounded object-cover" />
                                            <span>{{ suggestion.title }}</span>
                                            <span class="px-1.5 py-0.5 rounded text-xs font-medium"
                                                :class="suggestion.similarity >= 80
                                                    ? 'bg-green-200 text-green-800'
                                                    : suggestion.similarity >= 60
                                                        ? 'bg-yellow-200 text-yellow-800'
                                                        : 'bg-gray-200 text-gray-600'"
                                            >
                                                {{ suggestion.similarity }}%
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Local DB Search -->
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Search Local Database</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="item.searchQuery"
                                            @input="searchGame(item)"
                                            type="text"
                                            placeholder="Search your games..."
                                            class="flex-1 px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-gray-400 rounded focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        />
                                    </div>

                                    <!-- Local Search Results -->
                                    <div v-if="item.searchResults?.length" class="mt-2 flex flex-wrap gap-2">
                                        <button
                                            v-for="result in item.searchResults"
                                            :key="result.id"
                                            @click="linkToGame(item, result.id)"
                                            class="inline-flex items-center gap-2 px-2 py-1 bg-gray-50 text-gray-700 text-sm rounded hover:bg-green-50 hover:text-green-700 transition-colors border border-gray-200"
                                        >
                                            <img v-if="result.cover_url" :src="result.cover_url" class="w-10 h-14 rounded object-cover" />
                                            {{ result.title }}
                                        </button>
                                    </div>
                                </div>

                                <!-- IGDB Search -->
                                <div>
                                    <label class="text-xs font-medium text-purple-600 dark:text-purple-400 mb-1 block">Search IGDB (import new game)</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="item.igdbQuery"
                                            @input="searchIgdb(item)"
                                            type="text"
                                            :placeholder="'Search IGDB for ' + (item.psn_title || 'game') + '...'"
                                            class="flex-1 px-3 py-1.5 text-sm border border-purple-300 dark:border-purple-700 dark:bg-slate-700 dark:text-white dark:placeholder-gray-400 rounded focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        />
                                        <span v-if="item.igdbLoading" class="text-xs text-gray-400">Searching...</span>
                                    </div>

                                    <!-- IGDB Results -->
                                    <div v-if="item.igdbResults?.length" class="mt-2 flex flex-wrap gap-2">
                                        <button
                                            v-for="result in item.igdbResults"
                                            :key="result.igdb_id"
                                            @click="importAndLink(item, result)"
                                            :disabled="item.importing"
                                            class="inline-flex items-center gap-2 px-2 py-1 bg-purple-50 text-purple-700 text-sm rounded hover:bg-purple-100 transition-colors border border-purple-200"
                                        >
                                            <img v-if="result.cover_url" :src="result.cover_url" class="w-10 h-14 rounded object-cover" />
                                            <div class="text-left">
                                                <div>{{ result.title }}</div>
                                                <div class="text-xs text-purple-400">
                                                    {{ result.release_date ? result.release_date.substring(0, 4) : '' }}
                                                    {{ result.developer ? '• ' + result.developer : '' }}
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Create New Game -->
                                <div class="pt-2 border-t border-gray-200 dark:border-slate-700">
                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="!item.showCreateForm"
                                            @click="item.showCreateForm = true; item.newGameTitle = item.psn_title"
                                            class="text-xs text-orange-600 hover:text-orange-700 underline"
                                        >
                                            + Create new game manually
                                        </button>
                                        <template v-else>
                                            <input
                                                v-model="item.newGameTitle"
                                                type="text"
                                                placeholder="Game title..."
                                                class="flex-1 px-3 py-1.5 text-sm border border-orange-300 dark:border-orange-700 dark:bg-slate-700 dark:text-white dark:placeholder-gray-400 rounded focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                                @keyup.enter="createGame(item)"
                                            />
                                            <button
                                                @click="createGame(item)"
                                                :disabled="item.creating || !item.newGameTitle?.trim()"
                                                class="px-3 py-1.5 text-sm bg-orange-600 text-white rounded hover:bg-orange-700 disabled:opacity-50 transition-colors"
                                            >
                                                {{ item.creating ? '...' : 'Create' }}
                                            </button>
                                            <button
                                                @click="item.showCreateForm = false"
                                                class="px-2 py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                                            >
                                                Cancel
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2">
                    <button
                        @click="goToPage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-1 text-sm bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <span class="px-3 py-1 text-sm text-gray-600 dark:text-gray-400">
                        Page {{ pagination.current_page }} of {{ pagination.last_page }}
                    </span>
                    <button
                        @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-1 text-sm bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from './AdminLayout.vue'

const loading = ref(false)
const collecting = ref(false)
const autoMatching = ref(false)
const psnStoreMatching = ref(false)
const altNamesMatching = ref(false)
const unmatched = ref([])
const stats = ref(null)
const pagination = ref(null)
const collectUsername = ref('')
const collectResult = ref(null)
const searchQuery = ref('')
const selectedPlatform = ref('')
const sortBy = ref('similarity')
const currentPage = ref(1)
const skipCount = ref(0)

// Mass collector state
const massShowSection = ref(false)
const massHtml = ref('')
const massParsing = ref(false)
const massUsernames = ref([])
const massCollecting = ref(false)
const massCurrentUser = ref('')
const massCurrentIndex = ref(0)
const massTotalUsers = ref(0)
const massResults = ref([])
const massTotalNew = ref(0)
const massTotalAutoMatched = ref(0)

let searchTimeout = null
let loadTimeout = null

async function loadData() {
    await Promise.all([loadStats(), loadUnmatched()])
}

async function loadStats() {
    try {
        const response = await fetch('/api/admin/psn/stats')
        stats.value = await response.json()
    } catch (error) {
        console.error('Failed to load stats:', error)
    }
}

async function loadUnmatched() {
    loading.value = true
    try {
        const params = new URLSearchParams({
            page: currentPage.value,
            sort: sortBy.value,
            dir: sortBy.value === 'title' ? 'asc' : 'desc',
            per_page: 20
        })

        if (searchQuery.value) {
            params.append('search', searchQuery.value)
        }
        if (selectedPlatform.value) {
            params.append('platform', selectedPlatform.value)
        }

        const response = await fetch(`/api/admin/psn/unmatched?${params}`)
        const data = await response.json()

        unmatched.value = data.data.map(item => ({
            ...item,
            searchQuery: '',
            searchResults: [],
            // Keep suggestions from API response (preloaded with match probability)
            suggestions: item.suggestions || [],
            loadingSuggestions: false,
            igdbQuery: '',
            igdbResults: [],
            igdbLoading: false,
            importing: false,
            // Create game form
            showCreateForm: false,
            newGameTitle: '',
            creating: false,
            // Skip state
            skipping: false
        }))

        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            total: data.total
        }

        skipCount.value = data.skip_count || 0
    } catch (error) {
        console.error('Failed to load unmatched:', error)
    } finally {
        loading.value = false
    }
}

function debouncedLoad() {
    clearTimeout(loadTimeout)
    loadTimeout = setTimeout(() => {
        currentPage.value = 1
        loadUnmatched()
    }, 300)
}

function goToPage(page) {
    currentPage.value = page
    loadUnmatched()
}

async function collectFromUser() {
    if (!collectUsername.value.trim()) return

    collecting.value = true
    collectResult.value = null

    try {
        const response = await fetch(`/api/admin/psn/collect/${encodeURIComponent(collectUsername.value.trim())}`)

        if (!response.ok) {
            let errorMsg = `Server error (${response.status})`
            try {
                const errData = await response.json()
                errorMsg = errData.message || errorMsg
            } catch {
                // Response wasn't JSON (e.g. HTML 500 page)
                if (response.status === 504) {
                    errorMsg = 'Gateway timeout — the PSN library is too large and the request timed out. Try a user with fewer games.'
                } else if (response.status === 500) {
                    errorMsg = 'Server error — the PSN API may have timed out. Try again.'
                }
            }
            collectResult.value = { success: false, message: errorMsg }
            return
        }

        const data = await response.json()

        // Extract "Did you mean" suggestions from error message
        let suggestions = []
        if (!data.success && data.message) {
            const match = data.message.match(/Did you mean: (.+)\?/)
            if (match) {
                suggestions = match[1].split(', ').map(s => s.trim())
            }
        }

        collectResult.value = {
            success: data.success,
            message: data.success
                ? `Collected ${data.new_titles} new titles from ${data.username} (${data.existing_titles} already existed, ${data.auto_matched || 0} auto-matched)`
                : data.message,
            suggestions,
        }

        if (data.success) {
            collectUsername.value = ''
            await loadData()
        }
    } catch (error) {
        collectResult.value = {
            success: false,
            message: 'Network error — could not reach server. Check your connection and try again.'
        }
    } finally {
        collecting.value = false
    }
}

async function autoMatchPsnStore() {
    psnStoreMatching.value = true
    collectResult.value = null

    let totalMatched = 0
    let totalSearched = 0
    let offset = 0
    let totalGames = 0

    try {
        while (true) {
            collectResult.value = {
                success: true,
                message: `PSN Store: processing batch at ${offset}/${totalGames || '?'}... (${totalMatched} matched so far)`
            }

            const response = await fetch('/api/admin/psn/auto-match-psn-store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ offset })
            })
            const data = await response.json()

            if (!data.success) {
                collectResult.value = { success: false, message: data.message || 'Failed' }
                break
            }

            totalMatched += data.matched_count || 0
            totalSearched += data.searched_count || 0
            totalGames = data.total_games

            if (data.done) {
                collectResult.value = {
                    success: true,
                    message: `PSN Store: done. Searched ${totalSearched} games, matched ${totalMatched} of ${totalGames}.`
                }
                break
            }

            offset = data.next_offset
        }

        if (totalMatched > 0) {
            await loadData()
        }
    } catch (error) {
        collectResult.value = {
            success: false,
            message: `PSN Store: error at batch ${offset}. ${totalMatched} matched so far.`
        }
    } finally {
        psnStoreMatching.value = false
    }
}

async function autoMatchAltNames() {
    altNamesMatching.value = true
    collectResult.value = null

    let totalMatched = 0
    let totalSearched = 0
    let offset = 0
    let totalGames = 0

    try {
        while (true) {
            collectResult.value = {
                success: true,
                message: `IGDB Alt Names: processing batch at ${offset}/${totalGames || '?'}... (${totalMatched} matched so far)`
            }

            const response = await fetch('/api/admin/psn/auto-match-alt-names', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ offset })
            })
            const data = await response.json()

            if (!data.success) {
                collectResult.value = { success: false, message: data.message || 'Failed' }
                break
            }

            totalMatched += data.matched_count || 0
            totalSearched += data.searched_count || 0
            totalGames = data.total_games

            if (data.done) {
                collectResult.value = {
                    success: true,
                    message: `IGDB Alt Names: done. Searched ${totalSearched} names, matched ${totalMatched} of ${totalGames}.`
                }
                break
            }

            offset = data.next_offset
        }

        if (totalMatched > 0) {
            await loadData()
        }
    } catch (error) {
        collectResult.value = {
            success: false,
            message: `IGDB Alt Names: error at batch ${offset}. ${totalMatched} matched so far.`
        }
    } finally {
        altNamesMatching.value = false
    }
}

async function autoMatchAll() {
    autoMatching.value = true
    collectResult.value = null

    try {
        const response = await fetch('/api/admin/psn/auto-match-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        const data = await response.json()

        collectResult.value = {
            success: data.success,
            message: data.success
                ? `Auto-matched ${data.matched_count} titles. ${data.remaining_unmatched} still unmatched.`
                : data.message || 'Failed to auto-match'
        }

        if (data.success && data.matched_count > 0) {
            await loadData()
        }
    } catch (error) {
        collectResult.value = {
            success: false,
            message: 'Failed to run auto-match'
        }
    } finally {
        autoMatching.value = false
    }
}

async function createGame(item) {
    if (!item.newGameTitle?.trim()) return

    item.creating = true
    try {
        const response = await fetch('/api/admin/psn/create-game', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                psn_title_id: item.id,
                title: item.newGameTitle.trim()
            })
        })

        const data = await response.json()

        if (data.success) {
            // Remove from unmatched list
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            // Update stats
            if (stats.value) {
                stats.value.matched_titles++
                stats.value.unmatched_titles--
            }
        } else {
            alert(data.message || 'Failed to create game')
        }
    } catch (error) {
        console.error('Create failed:', error)
        alert('Failed to create game')
    } finally {
        item.creating = false
    }
}

async function skipItem(item) {
    item.skipping = true
    try {
        const response = await fetch('/api/admin/psn/skip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ psn_title_id: item.id })
        })

        if (response.ok) {
            item.is_skipped = true
            item.suggestions = [] // Clear suggestions for skipped items
            // Move to end of list
            unmatched.value = [
                ...unmatched.value.filter(u => u.id !== item.id && !u.is_skipped),
                ...unmatched.value.filter(u => u.is_skipped || u.id === item.id)
            ]
            skipCount.value++
        }
    } catch (error) {
        console.error('Skip failed:', error)
    } finally {
        item.skipping = false
    }
}

async function unskipItem(item) {
    item.skipping = true
    try {
        const response = await fetch('/api/admin/psn/unskip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ psn_title_id: item.id })
        })

        if (response.ok) {
            item.is_skipped = false
            skipCount.value--
            // Reload to get proper sorting and suggestions
            await loadUnmatched()
        }
    } catch (error) {
        console.error('Unskip failed:', error)
    } finally {
        item.skipping = false
    }
}

async function clearSkipped() {
    try {
        const response = await fetch('/api/admin/psn/clear-skips', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })

        if (response.ok) {
            skipCount.value = 0
            await loadUnmatched()
        }
    } catch (error) {
        console.error('Clear skips failed:', error)
    }
}

async function searchGame(item) {
    clearTimeout(searchTimeout)
    if (!item.searchQuery || item.searchQuery.length < 2) {
        item.searchResults = []
        return
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/api/admin/games/search-for-linking?query=${encodeURIComponent(item.searchQuery)}`)
            item.searchResults = await response.json()
        } catch (error) {
            console.error('Search failed:', error)
        }
    }, 300)
}

async function getSuggestions(item) {
    item.loadingSuggestions = true
    try {
        const response = await fetch(`/api/admin/psn/suggestions/${item.id}`)
        const data = await response.json()
        item.suggestions = data.suggestions
    } catch (error) {
        console.error('Failed to get suggestions:', error)
    } finally {
        item.loadingSuggestions = false
    }
}

let igdbTimeout = null

async function searchIgdb(item) {
    clearTimeout(igdbTimeout)
    if (!item.igdbQuery || item.igdbQuery.length < 2) {
        item.igdbResults = []
        return
    }

    igdbTimeout = setTimeout(async () => {
        item.igdbLoading = true
        try {
            const response = await fetch(`/api/admin/psn/search-igdb?query=${encodeURIComponent(item.igdbQuery)}`)
            const data = await response.json()
            item.igdbResults = data.results || []
        } catch (error) {
            console.error('IGDB search failed:', error)
            item.igdbResults = []
        } finally {
            item.igdbLoading = false
        }
    }, 400)
}

async function importAndLink(item, igdbGame) {
    item.importing = true
    try {
        const response = await fetch('/api/admin/psn/import-igdb-and-link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                psn_title_id: item.id,
                igdb_game: igdbGame
            })
        })

        const data = await response.json()

        if (data.success) {
            // Remove from unmatched list
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            // Update stats
            if (stats.value) {
                stats.value.matched_titles++
                stats.value.unmatched_titles--
            }
        } else {
            alert(data.message || 'Failed to import and link')
        }
    } catch (error) {
        console.error('Import failed:', error)
        alert('Failed to import game from IGDB')
    } finally {
        item.importing = false
    }
}

async function linkToGame(item, gameId) {
    try {
        const response = await fetch('/api/admin/psn/link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                psn_title_id: item.id,
                game_id: gameId
            })
        })

        const data = await response.json()

        if (data.success) {
            // Remove from unmatched list
            unmatched.value = unmatched.value.filter(u => u.id !== item.id)
            // Update stats
            if (stats.value) {
                stats.value.matched_titles++
                stats.value.unmatched_titles--
            }
        } else {
            alert(data.message || 'Failed to link')
        }
    } catch (error) {
        console.error('Link failed:', error)
        alert('Failed to link')
    }
}

async function parseMassHtml() {
    massParsing.value = true
    massUsernames.value = []
    massResults.value = []
    massTotalNew.value = 0
    massTotalAutoMatched.value = 0

    try {
        const response = await fetch('/api/admin/psn/parse-leaderboard', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ html: massHtml.value })
        })
        const data = await response.json()

        if (data.success) {
            massUsernames.value = data.usernames.map(u => ({ username: u, selected: true, status: null, message: '' }))
        } else {
            alert(data.message || 'Failed to parse usernames')
        }
    } catch (error) {
        alert('Failed to parse HTML')
    } finally {
        massParsing.value = false
    }
}

function massToggleAll() {
    const allSelected = massUsernames.value.every(u => u.selected)
    massUsernames.value.forEach(u => u.selected = !allSelected)
}

async function startMassCollection() {
    const selected = massUsernames.value.filter(u => u.selected)
    if (!selected.length) return

    massCollecting.value = true
    massResults.value = []
    massTotalNew.value = 0
    massTotalAutoMatched.value = 0
    massTotalUsers.value = selected.length

    let consecutiveZeroNew = 0

    for (let i = 0; i < selected.length; i++) {
        const user = selected[i]
        massCurrentIndex.value = i
        massCurrentUser.value = user.username
        user.status = 'processing'
        user.message = 'Collecting...'

        try {
            const response = await fetch(`/api/admin/psn/collect/${encodeURIComponent(user.username)}`)

            if (!response.ok) {
                let errorMsg = `Error (${response.status})`
                try {
                    const errData = await response.json()
                    errorMsg = errData.message || errorMsg
                } catch {
                    if (response.status === 504) errorMsg = 'Timeout'
                }
                user.status = 'error'
                user.message = errorMsg
                massResults.value.push({ success: false, username: user.username, message: errorMsg })
                continue
            }

            const data = await response.json()
            if (data.success) {
                user.status = 'success'
                user.message = `${data.new_titles} new, ${data.auto_matched || 0} matched`
                massTotalNew.value += data.new_titles || 0
                massTotalAutoMatched.value += data.auto_matched || 0
                massResults.value.push({ success: true, username: user.username, message: user.message })

                // Stop early if 3 consecutive users had 0 new titles
                consecutiveZeroNew = (data.new_titles || 0) === 0 ? consecutiveZeroNew + 1 : 0
                if (consecutiveZeroNew >= 3) {
                    // Mark remaining as skipped
                    for (let j = i + 1; j < selected.length; j++) {
                        selected[j].status = 'skipped'
                        selected[j].message = 'Skipped (no new titles from previous users)'
                    }
                    break
                }
            } else {
                user.status = 'error'
                user.message = data.message || 'Failed'
                massResults.value.push({ success: false, username: user.username, message: user.message })
            }
        } catch (error) {
            user.status = 'error'
            user.message = 'Network error'
            massResults.value.push({ success: false, username: user.username, message: 'Network error' })
        }
    }

    massCollecting.value = false
    await loadData()
}

onMounted(() => {
    loadData()
})
</script>
