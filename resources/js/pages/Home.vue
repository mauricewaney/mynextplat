<template>
    <AppLayout>
        <template #nav-tabs>
            <!-- Desktop View Mode Tabs -->
            <div class="hidden sm:flex items-center gap-2">
                <div class="flex bg-gray-100 dark:bg-slate-800 rounded-lg p-1">
                    <button @click="switchViewMode('all')" :class="['px-3 py-1.5 rounded-md text-sm font-medium transition-colors', viewMode === 'all' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-700/50']">
                        All Games
                    </button>
                    <button v-if="isPsnLoaded" @click="switchViewMode('psn')" :class="['px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5', viewMode === 'psn' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-700/50']">
                        PSN: {{ psnUser?.username }}
                    </button>
                    <router-link v-if="isAuthenticated" to="/my-games" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-700/50">
                        My Games
                    </router-link>
                </div>
                <button @click="showPsnSearchModal = true" class="px-2 py-1 text-xs font-bold text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors" :title="isPsnLoaded ? 'Load different PSN' : 'Load PSN Library'">
                    PSN
                </button>
            </div>
            <!-- Mobile View Mode Tabs -->
            <div class="sm:hidden flex bg-gray-100 dark:bg-slate-800 rounded-lg p-0.5">
                <button @click="switchViewMode('all')" :class="['px-2.5 py-1 rounded-md text-xs font-medium transition-colors', viewMode === 'all' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400']">
                    All Games
                </button>
                <button v-if="isPsnLoaded" @click="switchViewMode('psn')" :class="['px-2.5 py-1 rounded-md text-xs font-medium transition-colors', viewMode === 'psn' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400']">
                    PSN
                </button>
                <router-link v-if="isAuthenticated" to="/my-games" class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors text-gray-600 dark:text-gray-400">
                    My Games
                </router-link>
            </div>
        </template>

        <template #header-mobile>
            <button @click="showPsnSearchModal = true" class="px-2 py-1 text-xs font-bold rounded-lg transition-colors text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-slate-800" :title="isPsnLoaded ? 'Load different PSN' : 'Load PSN Library'">
                PSN
            </button>
        </template>

        <h1 class="sr-only">PlayStation Trophy Guides, Platinum Difficulty Ratings & Completion Times</h1>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex gap-8">
                <!-- Sidebar Filters (Desktop) -->
                <aside class="hidden lg:block w-[420px] shrink-0">
                    <div class="sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto pr-2 scrollbar-thin">
                        <GameFilters @update:filters="onFilterChange" />
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 min-w-0">
                    <!-- PSN View Info Bar (when in PSN view mode) -->
                    <div v-if="viewMode === 'psn' && isPsnLoaded" class="mb-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800/50 rounded-xl p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <!-- User Info -->
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="psnUser?.avatar"
                                    :src="psnUser.avatar"
                                    :alt="psnUser.username"
                                    class="w-10 h-10 rounded-full ring-2 ring-blue-300 dark:ring-blue-700"
                                />
                                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold" v-else>
                                    {{ psnUser?.username?.charAt(0)?.toUpperCase() }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5m-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6m6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5m-6 8h1.5v1.5H13V13m1.5 1.5H16V16h-1.5v-1.5M16 13h1.5v1.5H16V13m-3 3h1.5v1.5H13V16m1.5 1.5H16V19h-1.5v-1.5M16 16h1.5v1.5H16V16m1.5-1.5H19V16h-1.5v-1.5m0 3H19V19h-1.5v-1.5M19 13h-1.5v1.5H19V13"/>
                                        </svg>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ psnUser?.username }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ psnStats.total_psn_games }} games &middot;
                                        {{ psnStats.matched_games }} matched &middot;
                                        {{ psnStats.has_guide }} with guides &middot;
                                        <button
                                            v-if="psnStats.unmatched_games > 0"
                                            @click="showUnmatchedModal = true"
                                            class="text-blue-600 dark:text-blue-400 hover:underline"
                                        >
                                            {{ psnStats.unmatched_games }} unmatched
                                        </button>
                                        <span v-else>{{ psnStats.unmatched_games }} unmatched</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Controls -->
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Guide Only Toggle -->
                                <label class="flex items-center gap-2 cursor-pointer bg-white dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-slate-700">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Guide only</span>
                                    <div class="relative" @click="toggleHasGuideOnly">
                                        <div :class="[
                                            'w-9 h-5 rounded-full transition-colors',
                                            psnHasGuideOnly ? 'bg-blue-600' : 'bg-gray-300 dark:bg-slate-600'
                                        ]"></div>
                                        <div :class="[
                                            'absolute top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-all duration-200',
                                            psnHasGuideOnly ? 'left-[18px]' : 'left-0.5'
                                        ]"></div>
                                    </div>
                                </label>

                                <!-- Add All to Library -->
                                <button
                                    v-if="isAuthenticated"
                                    @click="showBulkAddConfirm = true"
                                    :disabled="bulkAddLoading"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors disabled:opacity-50"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Add All to Library
                                </button>
                                <button
                                    v-else
                                    @click="showLoginPrompt = true"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Sign in to Add
                                </button>

                                <!-- Clear PSN -->
                                <button
                                    @click="clearPsnLibrary"
                                    class="p-1.5 text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                    title="Clear PSN Library"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sort Bar (All screen sizes) -->
                    <div class="flex items-center justify-between mb-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm p-3">
                        <div class="flex items-center gap-2">
                            <!-- Filter Button (Mobile) -->
                            <button
                                @click="showMobileFilters = true"
                                class="lg:hidden flex items-center justify-center p-2 rounded-full transition-all ring-1 bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 ring-gray-200 dark:ring-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                            </button>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ total.toLocaleString() }}</span> games
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="hidden sm:block text-sm text-gray-500 dark:text-gray-400">Sort:</label>
                            <select
                                v-model="sortBy"
                                @change="loadGames"
                                class="border-0 bg-gray-100 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="title">Title</option>
                                <option value="release_date">Release Date</option>
                                <option value="added_at">Date Added</option>
                                <option value="difficulty">Difficulty</option>
                                <option value="time_min">Completion Time</option>
                                <option value="user_score">User Score</option>
                                <option value="critic_score">Critic Score</option>
                                <option value="playthroughs_required">Playthroughs</option>
                                <option value="missable_trophies">Missables</option>
                            </select>
                            <button
                                @click="toggleSortOrder"
                                class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                :title="sortOrder === 'asc' ? 'Ascending' : 'Descending'"
                            >
                                <svg
                                    :class="['w-5 h-5 transition-transform', sortOrder === 'desc' ? 'rotate-180' : '']"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading && games.length === 0" class="space-y-4">
                        <div
                            v-for="n in 6"
                            :key="n"
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm animate-pulse flex gap-4 p-3 sm:p-4"
                        >
                            <div class="w-24 sm:w-28 h-32 sm:h-36 shrink-0 bg-gray-200 dark:bg-slate-700 rounded-lg"></div>
                            <div class="flex-1 space-y-3">
                                <div class="h-5 bg-gray-200 dark:bg-slate-700 rounded w-3/4"></div>
                                <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded w-1/2"></div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded"></div>
                                </div>
                                <div class="flex gap-2 pt-2">
                                    <div class="h-6 bg-gray-200 dark:bg-slate-700 rounded w-12"></div>
                                    <div class="h-6 bg-gray-200 dark:bg-slate-700 rounded w-12"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-else-if="!loading && games.length === 0"
                        class="text-center py-16"
                    >
                        <!-- PSN View - Not Loaded -->
                        <template v-if="viewMode === 'psn' && !isPsnLoaded">
                            <svg class="w-16 h-16 mx-auto text-blue-300 dark:text-blue-600 mb-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5m-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6m6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5m-6 8h1.5v1.5H13V13m1.5 1.5H16V16h-1.5v-1.5M16 13h1.5v1.5H16V13m-3 3h1.5v1.5H13V16m1.5 1.5H16V19h-1.5v-1.5M16 16h1.5v1.5H16V16m1.5-1.5H19V16h-1.5v-1.5m0 3H19V19h-1.5v-1.5M19 13h-1.5v1.5H19V13"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Load a PSN Library</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Enter a PSN username to see their game library</p>
                            <button
                                @click="showPsnSearchModal = true"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                            >
                                Enter PSN Username
                            </button>
                        </template>

                        <!-- Default Empty State -->
                        <template v-else>
                            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No games found</h3>
                            <p class="text-gray-500 dark:text-gray-400">Try adjusting your filters</p>
                        </template>
                    </div>

                    <!-- Games List -->
                    <div v-else class="space-y-4">
                        <GameCard
                            v-for="game in games"
                            :key="game.id"
                            :game="game"
                            @update-status="updateGameStatus"
                        />
                    </div>

                    <!-- Load More / Pagination -->
                    <div v-if="hasMore && games.length > 0" class="mt-8 text-center">
                        <button
                            @click="loadMore"
                            :disabled="loading"
                            class="px-8 py-3 bg-white dark:bg-slate-800 text-primary-600 dark:text-primary-400 font-medium rounded-xl shadow-sm hover:shadow-md dark:shadow-slate-900/50 transition-all disabled:opacity-50"
                        >
                            <span v-if="loading" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading...
                            </span>
                            <span v-else>Load more games</span>
                        </button>
                    </div>

                    <!-- Showing count -->
                    <div v-if="games.length > 0" class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        Showing {{ games.length }} of {{ total.toLocaleString() }} games
                    </div>
                </main>
            </div>
        </div>

        <!-- Mobile Filters Bottom Sheet -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showMobileFilters"
                    class="fixed inset-0 z-50 lg:hidden"
                    @click.self="showMobileFilters = false"
                >
                    <div class="absolute inset-0 bg-black/50" @click="showMobileFilters = false"></div>
                    <Transition name="slide-up">
                        <div
                            v-if="showMobileFilters"
                            class="absolute inset-x-0 bottom-0 max-h-[85vh] bg-gray-50 dark:bg-slate-900 shadow-xl rounded-t-2xl flex flex-col"
                        >
                            <!-- Handle bar -->
                            <div class="flex justify-center pt-3 pb-1">
                                <div class="w-10 h-1 bg-gray-300 dark:bg-slate-600 rounded-full"></div>
                            </div>
                            <!-- Header -->
                            <div class="bg-white dark:bg-slate-800 border-b border-gray-100 dark:border-slate-700 px-4 py-3 flex items-center justify-between">
                                <h2 class="font-semibold text-gray-900 dark:text-white">Filters</h2>
                                <button
                                    @click="showMobileFilters = false"
                                    class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <!-- Scrollable content -->
                            <div class="flex-1 overflow-y-auto p-4 pb-24">
                                <GameFilters @update:filters="onFilterChange" />
                            </div>
                            <!-- Sticky footer with Show Results button -->
                            <div class="sticky bottom-0 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700 p-4">
                                <button
                                    @click="showMobileFilters = false"
                                    class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
                                >
                                    Show {{ total.toLocaleString() }} Results
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Login Prompt Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showLoginPrompt"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div class="absolute inset-0 bg-black/50" @click="showLoginPrompt = false"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 text-center">
                        <button
                            @click="showLoginPrompt = false"
                            class="absolute top-4 right-4 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <div class="w-16 h-16 mx-auto mb-4 bg-primary-100 dark:bg-primary-900/50 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Sign in required</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">Sign in with Google to access your game list and save your progress.</p>
                        <button
                            @click="loginWithGoogle"
                            class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Sign in with Google
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Bulk Add Confirmation Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showBulkAddConfirm"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div class="absolute inset-0 bg-black/50" @click="showBulkAddConfirm = false"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 text-center">
                        <button
                            @click="showBulkAddConfirm = false"
                            class="absolute top-4 right-4 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            :disabled="bulkAddLoading"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Add All PSN Games</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Add all <strong class="text-gray-700 dark:text-gray-200">{{ psnAllGameIds?.length || 0 }}</strong> matched games from {{ psnUser?.username }}'s PSN library to your personal library?
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                            Games already in your library will be skipped.
                        </p>
                        <div class="flex gap-3">
                            <button
                                @click="showBulkAddConfirm = false"
                                :disabled="bulkAddLoading"
                                class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors disabled:opacity-50"
                            >
                                Cancel
                            </button>
                            <button
                                @click="bulkAddPsnGamesToLibrary"
                                :disabled="bulkAddLoading"
                                class="flex-1 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                <svg v-if="bulkAddLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ bulkAddLoading ? 'Adding...' : 'Add All' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- PSN Search Modal (works on all screen sizes) -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showPsnSearchModal && !isPsnLoaded"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div class="absolute inset-0 bg-black/50" @click="showPsnSearchModal = false"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6">
                        <button
                            @click="showPsnSearchModal = false"
                            class="absolute top-4 right-4 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>

                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5m-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6m6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5m-6 8h1.5v1.5H13V13m1.5 1.5H16V16h-1.5v-1.5M16 13h1.5v1.5H16V13m-3 3h1.5v1.5H13V16m1.5 1.5H16V19h-1.5v-1.5M16 16h1.5v1.5H16V16m1.5-1.5H19V16h-1.5v-1.5m0 3H19V19h-1.5v-1.5M19 13h-1.5v1.5H19V13"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Load PSN Library</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Enter a PSN username to browse their game library</p>
                            </div>
                        </div>

                        <form @submit.prevent="handlePsnLookup" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PSN Username</label>
                                <input
                                    v-model="psnUsernameInput"
                                    type="text"
                                    placeholder="Enter username..."
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-sm dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                                    :disabled="psnLoading"
                                    autofocus
                                />
                            </div>

                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="showPsnSearchModal = false"
                                    class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="psnLoading || !psnUsernameInput.trim()"
                                    class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                                >
                                    <svg v-if="psnLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ psnLoading ? 'Loading...' : 'Load Library' }}
                                </button>
                            </div>
                        </form>

                        <button
                            v-if="isAdmin"
                            @click="loadMyPsnLibrary"
                            :disabled="psnLoading"
                            class="w-full mt-3 px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium disabled:opacity-50 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                        >
                            Load My PSN Library
                        </button>

                        <p v-if="psnError" class="mt-3 text-sm text-red-500 dark:text-red-400 text-center">{{ psnError }}</p>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Unmatched Games Modal -->
        <Teleport to="body">
            <div
                v-if="showUnmatchedModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="showUnmatchedModal = false"
            >
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-lg w-full max-h-[80vh] flex flex-col">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            Unmatched PSN Games ({{ psnUnmatchedTitles.length }})
                        </h3>
                        <button
                            @click="showUnmatchedModal = false"
                            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="px-4 py-2 border-b border-gray-100 dark:border-slate-700">
                        <input
                            v-model="unmatchedSearch"
                            type="text"
                            placeholder="Search unmatched..."
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-sm dark:text-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 placeholder-gray-400 dark:placeholder-gray-500"
                        />
                    </div>

                    <!-- List -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            These games from your PSN library couldn't be matched to games in our database. They may be apps, DLC, or games not yet added.
                        </p>
                        <ul class="space-y-1">
                            <li
                                v-for="(title, index) in filteredUnmatchedTitles"
                                :key="index"
                                class="text-sm text-gray-700 dark:text-gray-300 py-1.5 px-2 rounded hover:bg-gray-50 dark:hover:bg-slate-700"
                            >
                                {{ title }}
                            </li>
                        </ul>
                        <p v-if="filteredUnmatchedTitles.length === 0" class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">
                            No matches found
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/50 rounded-b-xl">
                        <button
                            @click="showUnmatchedModal = false"
                            class="w-full px-4 py-2 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import AppLayout from '../components/AppLayout.vue'
import GameCard from '../components/GameCard.vue'
import GameFilters from '../components/GameFilters.vue'
import { useAuth } from '../composables/useAuth'
import { useAppConfig } from '../composables/useAppConfig'
import { usePSNLibrary } from '../composables/usePSNLibrary'
import { useUserGames } from '../composables/useUserGames'

const { appName } = useAppConfig()

// SEO Meta Tags
const ogImage = `${window.location.origin}/images/og-banner.png`

useHead({
    title: `${appName} - PlayStation Trophy Guides & Tracker`,
    link: [
        { rel: 'canonical', href: window.location.origin + '/' },
    ],
    meta: [
        { name: 'description', content: 'Find your next platinum trophy. Browse PlayStation trophy guides from PSNProfiles, PlayStationTrophies, and PowerPyx. Filter by difficulty, time, and more.' },
        { property: 'og:title', content: `${appName} - PlayStation Trophy Guides & Tracker` },
        { property: 'og:description', content: 'Find your next platinum trophy. Browse PlayStation trophy guides from PSNProfiles, PlayStationTrophies, and PowerPyx.' },
        { property: 'og:type', content: 'website' },
        { property: 'og:image', content: ogImage },
        { property: 'og:image:width', content: '1200' },
        { property: 'og:image:height', content: '630' },
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: `${appName} - PlayStation Trophy Guides & Tracker` },
        { name: 'twitter:description', content: 'Find your next platinum trophy. Browse PlayStation trophy guides with filters for difficulty, time, and more.' },
        { name: 'twitter:image', content: ogImage },
        { name: 'keywords', content: 'playstation, trophy guide, platinum trophy, ps5, ps4, psnprofiles, powerpyx, trophy hunting' },
    ],
})

const route = useRoute()
const router = useRouter()
const { user, isAuthenticated, isAdmin, loginWithGoogle } = useAuth()
const {
    psnGameIds,
    psnAllGameIds,
    isPsnLoaded,
    psnUnmatchedTitles,
    psnUser,
    psnStats,
    psnLoading,
    psnError,
    lookupPSN,
    loadMyLibrary,
    clearPSN,
    toggleHasGuideOnly,
    psnHasGuideOnly,
    psnFilteredCount,
} = usePSNLibrary()

const { bulkAddToList, updateStatus } = useUserGames()

const psnUsernameInput = ref('')

async function handlePsnLookup() {
    if (psnUsernameInput.value.trim()) {
        await lookupPSN(psnUsernameInput.value.trim())
        // Modal will be closed by the isPsnLoaded watcher when successful
    }
}

function loadMyPsnLibrary() {
    loadMyLibrary()
}

function clearPsnLibrary() {
    clearPSN()
    psnUsernameInput.value = ''
    // Switch back to 'all' view when clearing PSN
    if (viewMode.value === 'psn') {
        viewMode.value = 'all'
        sessionStorage.setItem('viewMode', 'all')
        currentPage.value = 1
        games.value = []
        loadGames()
    }
}

// Bulk add all PSN games to user's library
async function bulkAddPsnGamesToLibrary() {
    if (!isAuthenticated.value) {
        showLoginPrompt.value = true
        return
    }

    const gameIds = psnAllGameIds.value
    if (!gameIds?.length) return

    bulkAddLoading.value = true
    try {
        const result = await bulkAddToList(gameIds)
        showBulkAddConfirm.value = false
        // Show success message or notification could be added here
        alert(`Added ${result.added} games to your library${result.skipped > 0 ? ` (${result.skipped} already in list)` : ''}`)
    } catch (e) {
        alert('Failed to add games: ' + e.message)
    } finally {
        bulkAddLoading.value = false
    }
}

const showPsnSearchModal = ref(false)
const showUnmatchedModal = ref(false)
const unmatchedSearch = ref('')
const showLoginPrompt = ref(false)
const viewMode = ref(sessionStorage.getItem('viewMode') || 'all') // 'all' | 'psn'
const bulkAddLoading = ref(false)
const showBulkAddConfirm = ref(false)

// Check for login required query param
watch(() => route.query.login, (val) => {
    if (val === 'required') {
        showLoginPrompt.value = true
        // Clear the login param while preserving others
        const { login, ...rest } = route.query
        router.replace({ query: rest })
    }
}, { immediate: true })

// Watch PSN loaded state - auto-switch to PSN view when library loads
watch(isPsnLoaded, (loaded) => {
    if (loaded) {
        // Close PSN search modal
        showPsnSearchModal.value = false
        // Auto-switch to PSN view
        viewMode.value = 'psn'
        sessionStorage.setItem('viewMode', 'psn')
        currentPage.value = 1
        games.value = []
        loadGames()
    }
})

// Watch PSN game IDs (filtered by guide toggle) and reload games when they change
watch(psnGameIds, (newIds, oldIds) => {
    // Only reload if we're in PSN view and IDs actually changed
    if (viewMode.value === 'psn' && JSON.stringify(newIds) !== JSON.stringify(oldIds)) {
        currentPage.value = 1
        games.value = []
        loadGames()
    }
})


// Filtered unmatched titles for the modal
const filteredUnmatchedTitles = computed(() => {
    if (!unmatchedSearch.value.trim()) {
        return psnUnmatchedTitles.value
    }
    const search = unmatchedSearch.value.toLowerCase()
    return psnUnmatchedTitles.value.filter(title =>
        title.toLowerCase().includes(search)
    )
})




// --- Filter ↔ URL query param sync ---
// Maps internal filter keys to short URL param names
const FILTER_PARAM_MAP = {
    platform_ids: 'platforms',
    genre_ids: 'genres',
    tag_ids: 'tags',
    difficulty_min: 'diff_min',
    difficulty_max: 'diff_max',
    time_min: 'time_min',
    time_max: 'time_max',
    max_playthroughs: 'runs',
    user_score_min: 'uscore_min',
    user_score_max: 'uscore_max',
    critic_score_min: 'cscore_min',
    critic_score_max: 'cscore_max',
    has_online_trophies: 'online',
    missable_trophies: 'missable',
    has_guide: 'guide',
    guide_psnp: 'psnp',
    guide_pst: 'pst',
    guide_ppx: 'ppx',
}
const PARAM_FILTER_MAP = Object.fromEntries(
    Object.entries(FILTER_PARAM_MAP).map(([k, v]) => [v, k])
)

const FILTER_DEFAULTS = {
    platform_ids: [],
    genre_ids: [],
    tag_ids: [],
    difficulty_min: 1,
    difficulty_max: 10,
    time_min: 0,
    time_max: 200,
    max_playthroughs: null,
    user_score_min: 0,
    user_score_max: 100,
    critic_score_min: 0,
    critic_score_max: 100,
    has_online_trophies: null,
    missable_trophies: null,
    has_guide: null,
    guide_psnp: false,
    guide_pst: false,
    guide_ppx: false,
}

const ARRAY_KEYS = new Set(['platform_ids', 'genre_ids', 'tag_ids'])
const BOOL_KEYS = new Set(['has_online_trophies', 'missable_trophies', 'has_guide'])
const FLAG_KEYS = new Set(['guide_psnp', 'guide_pst', 'guide_ppx'])

// Non-filter query params we must preserve
const RESERVED_PARAMS = new Set(['login', 'view'])

function queryToFilters(query) {
    const result = {}
    let hasSomething = false

    for (const [paramName, filterKey] of Object.entries(PARAM_FILTER_MAP)) {
        const raw = query[paramName]
        if (raw == null || raw === '') continue

        hasSomething = true
        if (ARRAY_KEYS.has(filterKey)) {
            const nums = String(raw).split(',').map(Number).filter(n => !isNaN(n) && n > 0)
            if (nums.length) result[filterKey] = nums
        } else if (BOOL_KEYS.has(filterKey)) {
            if (raw === 'true') result[filterKey] = true
            else if (raw === 'false') result[filterKey] = false
        } else if (FLAG_KEYS.has(filterKey)) {
            result[filterKey] = raw === '1' || raw === 'true'
        } else {
            const n = Number(raw)
            if (!isNaN(n)) result[filterKey] = n
        }
    }

    // Parse sort params
    const parsedSort = {}
    if (query.sort) { parsedSort.sortBy = query.sort; hasSomething = true }
    if (query.order) { parsedSort.sortOrder = query.order; hasSomething = true }

    return hasSomething ? { filters: result, ...parsedSort } : null
}

const games = ref([])
const loading = ref(true)
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
// Load saved state — URL query params take priority over sessionStorage
const urlState = queryToFilters(route.query)
const sortBy = ref(urlState?.sortBy || sessionStorage.getItem('sortBy') || 'critic_score')
const sortOrder = ref(urlState?.sortOrder || sessionStorage.getItem('sortOrder') || 'desc')
const showMobileFilters = ref(false)

// Load filters: URL params first (shared link), then sessionStorage (returning user)
const savedFilters = (() => {
    if (urlState?.filters && Object.keys(urlState.filters).length) {
        return urlState.filters
    }
    try {
        const saved = sessionStorage.getItem('gameFilters')
        return saved ? JSON.parse(saved) : {}
    } catch {
        return {}
    }
})()
const filters = reactive({ ...savedFilters })

function filtersToQuery() {
    const query = {}

    // Preserve non-filter query params
    for (const key of RESERVED_PARAMS) {
        if (route.query[key] != null) {
            query[key] = route.query[key]
        }
    }

    for (const [filterKey, paramName] of Object.entries(FILTER_PARAM_MAP)) {
        const val = filters[filterKey]
        const def = FILTER_DEFAULTS[filterKey]

        if (ARRAY_KEYS.has(filterKey)) {
            if (val?.length) query[paramName] = val.join(',')
        } else if (BOOL_KEYS.has(filterKey)) {
            if (val === true) query[paramName] = 'true'
            else if (val === false) query[paramName] = 'false'
        } else if (FLAG_KEYS.has(filterKey)) {
            if (val) query[paramName] = '1'
        } else {
            // numeric — only include if different from default
            if (val != null && val !== def) query[paramName] = String(val)
        }
    }

    // Sort params
    if (sortBy.value !== 'critic_score') query.sort = sortBy.value
    if (sortOrder.value !== 'desc') query.order = sortOrder.value

    return query
}

function syncQueryToUrl() {
    router.replace({ query: filtersToQuery() })
}

const hasMore = computed(() => currentPage.value < lastPage.value)

function onFilterChange(newFilters) {
    Object.assign(filters, newFilters)
    // During mount, just sync filter values — parent onMounted will call loadGames()
    if (isMounting) return
    // Save filters to sessionStorage (exclude game_ids as they're from PSN lookup)
    const filtersToSave = { ...newFilters }
    delete filtersToSave.game_ids
    sessionStorage.setItem('gameFilters', JSON.stringify(filtersToSave))
    currentPage.value = 1
    games.value = []
    loadGames()
    syncQueryToUrl()
    // Don't auto-close mobile filters - let user adjust multiple filters
}

function toggleSortOrder() {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    sessionStorage.setItem('sortOrder', sortOrder.value)
    currentPage.value = 1
    games.value = []
    loadGames()
    syncQueryToUrl()
}

// Watch sortBy changes to save to sessionStorage and sync URL
watch(sortBy, (newVal) => {
    sessionStorage.setItem('sortBy', newVal)
    syncQueryToUrl()
})

function switchViewMode(mode) {
    // PSN tab only shows when loaded, but keep safety check
    if (mode === 'psn' && !isPsnLoaded.value) return

    viewMode.value = mode
    sessionStorage.setItem('viewMode', mode)
    currentPage.value = 1
    games.value = []
    loadGames()
}

let loadGamesController = null
let isMounting = true

async function loadGames() {
    // Cancel any in-flight request
    if (loadGamesController) {
        loadGamesController.abort()
    }
    loadGamesController = new AbortController()

    loading.value = true

    try {
        const params = new URLSearchParams()

        // PSN view - show games from PSN library
        if (viewMode.value === 'psn') {
            if (psnGameIds.value?.length) {
                params.append('game_ids', psnGameIds.value.join(','))
            } else {
                // No PSN games loaded - will show empty state
                loading.value = false
                games.value = []
                total.value = 0
                return
            }
        }
        // 'all' mode: no special filters

        // Has guide filter - use explicit filter if set, otherwise default to showing guides only for 'all' mode
        if (filters.has_guide === true) {
            params.append('has_guide', 'true')
        } else if (filters.has_guide === false) {
            params.append('has_guide', 'false')
        } else if (viewMode.value === 'all' && !filters.search) {
            // Default: show games with guides on homepage when browsing (not searching)
            params.append('has_guide', 'true')
        }

        // Add filters
        if (filters.search) params.append('search', filters.search)
        if (filters.platform_ids?.length) {
            filters.platform_ids.forEach(id => params.append('platform_ids[]', id))
        }
        if (filters.genre_ids?.length) {
            filters.genre_ids.forEach(id => params.append('genre_ids[]', id))
        }
        if (filters.tag_ids?.length) {
            filters.tag_ids.forEach(id => params.append('tag_ids[]', id))
        }
        if (filters.difficulty_min > 1) params.append('difficulty_min', filters.difficulty_min)
        if (filters.difficulty_max < 10) params.append('difficulty_max', filters.difficulty_max)
        if (filters.time_min > 0) params.append('time_min', filters.time_min)
        if (filters.time_max < 200) params.append('time_max', filters.time_max)
        if (filters.max_playthroughs) params.append('max_playthroughs', filters.max_playthroughs)
        if (filters.user_score_min > 0) params.append('user_score_min', filters.user_score_min)
        if (filters.user_score_max < 100) params.append('user_score_max', filters.user_score_max)
        if (filters.critic_score_min > 0) params.append('critic_score_min', filters.critic_score_min)
        if (filters.critic_score_max < 100) params.append('critic_score_max', filters.critic_score_max)
        if (filters.has_platinum === true) params.append('has_platinum', 'true')
        if (filters.has_online_trophies === false) params.append('has_online_trophies', 'false')
        if (filters.missable_trophies === false) params.append('missable_trophies', 'false')
        if (filters.guide_psnp) params.append('guide_psnp', 'true')
        if (filters.guide_pst) params.append('guide_pst', 'true')
        if (filters.guide_ppx) params.append('guide_ppx', 'true')

        // Sorting
        params.append('sort_by', sortBy.value)
        params.append('sort_order', sortOrder.value)

        // Pagination
        params.append('page', currentPage.value)
        params.append('per_page', 24)

        const response = await fetch(`/api/games?${params}`, {
            signal: loadGamesController.signal,
        })
        const data = await response.json()

        if (currentPage.value === 1) {
            games.value = data.data
        } else {
            games.value.push(...data.data)
        }

        total.value = data.total
        lastPage.value = data.last_page
    } catch (e) {
        if (e.name === 'AbortError') return // Request was cancelled by a newer one
        console.error('Failed to load games:', e)
    } finally {
        loading.value = false
    }
}

function loadMore() {
    currentPage.value++
    loadGames()
}

async function updateGameStatus(gameId, status) {
    try {
        await updateStatus(gameId, status)
        // Update local state
        const game = games.value.find(g => g.id === gameId)
        if (game) {
            game.user_status = status
        }
    } catch (e) {
        console.error('Failed to update status:', e)
    }
}

onMounted(() => {
    // Handle view query parameter (e.g., from My Games navigation tabs)
    if (route.query.view === 'psn') {
        if (isPsnLoaded.value) {
            viewMode.value = 'psn'
            sessionStorage.setItem('viewMode', 'psn')
        } else {
            // PSN not loaded, show the search modal
            showPsnSearchModal.value = true
            viewMode.value = 'all'
            sessionStorage.setItem('viewMode', 'all')
        }
    } else {
        // Reset to 'all' view if invalid view mode or PSN view was saved but PSN not loaded
        if (viewMode.value === 'library' || (viewMode.value === 'psn' && !isPsnLoaded.value)) {
            viewMode.value = 'all'
            sessionStorage.setItem('viewMode', 'all')
        }
    }

    // GameFilters.onMounted already synced saved filters via emitFilters → onFilterChange
    // Now that viewMode is set, do the single initial load
    isMounting = false
    loadGames()
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
}
</style>
