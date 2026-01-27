<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 transition-colors duration-300">
        <!-- Header -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-lg border-b border-gray-200 shadow-sm dark:bg-slate-900/95 dark:border-slate-700/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-4">
                        <router-link to="/" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h1 class="hidden sm:block text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent">
                                MyNextPlat
                            </h1>
                        </router-link>

                        <!-- View Mode Tabs - Desktop -->
                        <div class="hidden sm:flex bg-gray-100 dark:bg-slate-800 rounded-lg p-1">
                            <button
                                @click="switchViewMode('all')"
                                :class="[
                                    'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                                    viewMode === 'all'
                                        ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                All Games
                            </button>
                            <button
                                @click="switchViewMode('library')"
                                :class="[
                                    'px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5',
                                    viewMode === 'library'
                                        ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                My Library
                            </button>
                        </div>

                        <!-- View Mode Dropdown - Mobile -->
                        <div class="sm:hidden relative view-mode-menu-container">
                            <button
                                @click="showViewModeMenu = !showViewModeMenu"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-100 dark:bg-slate-800 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                <svg v-if="viewMode === 'library'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                <span>{{ viewMode === 'all' ? 'All' : 'Library' }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <!-- Dropdown Menu -->
                            <div
                                v-if="showViewModeMenu"
                                class="absolute top-full left-0 mt-1 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-gray-200 dark:border-slate-700 py-1 min-w-[120px] z-50"
                            >
                                <button
                                    @click="switchViewMode('all'); showViewModeMenu = false"
                                    :class="[
                                        'w-full px-3 py-2 text-left text-sm',
                                        viewMode === 'all'
                                            ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700'
                                    ]"
                                >
                                    All Games
                                </button>
                                <button
                                    @click="switchViewMode('library'); showViewModeMenu = false"
                                    :class="[
                                        'w-full px-3 py-2 text-left text-sm flex items-center gap-2',
                                        viewMode === 'library'
                                            ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700'
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                    My Library
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile actions -->
                    <div class="lg:hidden flex items-center gap-1">
                        <button
                            @click="toggleDarkMode"
                            class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                        >
                            <svg v-if="darkMode" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </button>
                        <!-- Mobile Auth -->
                        <button
                            v-if="isAuthenticated"
                            @click.stop="showUserMenu = !showUserMenu"
                            class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors user-menu-container shrink-0"
                        >
                            <img
                                v-if="user?.avatar"
                                :src="user.avatar"
                                :alt="user.name"
                                class="w-8 h-8 rounded-full object-cover aspect-square"
                            />
                            <div v-else class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-medium aspect-square shrink-0">
                                {{ user?.name?.charAt(0) || '?' }}
                            </div>
                        </button>
                        <button
                            v-else
                            @click="loginWithGoogle"
                            class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                            title="Sign in"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </button>
                        <button
                            @click="showMobileFilters = true"
                            class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Desktop stats -->
                    <div class="hidden lg:flex items-center gap-6 text-sm">
                        <div class="text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ total.toLocaleString() }}</span> games
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Sort:</label>
                            <select
                                v-model="sortBy"
                                @change="loadGames"
                                class="border-0 bg-gray-100 dark:bg-slate-800 dark:text-gray-200 rounded-lg text-sm py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="title">Title</option>
                                <option value="release_date">Release Date</option>
                                <option value="difficulty">Difficulty</option>
                                <option value="time_min">Completion Time</option>
                                <option value="critic_score">Critic Score</option>
                                <option value="playthroughs_required">Playthroughs</option>
                                <option value="missable_trophies">Missables</option>
                            </select>
                            <button
                                @click="toggleSortOrder"
                                class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
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
                        <button
                            @click="toggleDarkMode"
                            class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                            :title="darkMode ? 'Light mode' : 'Dark mode'"
                        >
                            <svg v-if="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </button>

                        <!-- Auth UI -->
                        <div v-if="isAuthenticated" class="relative user-menu-container">
                            <button
                                @click.stop="showUserMenu = !showUserMenu"
                                class="flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
                            >
                                <img
                                    v-if="user?.avatar"
                                    :src="user.avatar"
                                    :alt="user.name"
                                    class="w-8 h-8 rounded-full"
                                />
                                <div v-else class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-medium">
                                    {{ user?.name?.charAt(0) || '?' }}
                                </div>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <Transition name="dropdown">
                                <div
                                    v-if="showUserMenu"
                                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-gray-200 dark:border-slate-700 py-1 z-50"
                                >
                                    <div class="px-4 py-2 border-b border-gray-100 dark:border-slate-700">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user?.email }}</p>
                                    </div>
                                    <router-link
                                        to="/my-games"
                                        @click="showUserMenu = false"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700"
                                    >
                                        My Games
                                    </router-link>
                                    <router-link
                                        v-if="isAdmin"
                                        to="/admin"
                                        @click="showUserMenu = false"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700"
                                    >
                                        Admin
                                    </router-link>
                                    <button
                                        @click="handleLogout"
                                        class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-slate-700"
                                    >
                                        Sign Out
                                    </button>
                                </div>
                            </Transition>
                        </div>

                        <!-- Login Button -->
                        <button
                            v-else
                            @click="loginWithGoogle"
                            class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Sign in
                        </button>
                    </div>
                </div>
            </div>
        </header>

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
                    <!-- Mobile Sort -->
                    <div class="lg:hidden flex items-center justify-between mb-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm p-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ total.toLocaleString() }}</span> games
                        </span>
                        <div class="flex items-center gap-2">
                            <select
                                v-model="sortBy"
                                @change="loadGames"
                                class="border-0 bg-gray-100 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="title">Title</option>
                                <option value="release_date">Release Date</option>
                                <option value="difficulty">Difficulty</option>
                                <option value="time_min">Time</option>
                                <option value="critic_score">Score</option>
                                <option value="playthroughs_required">Playthroughs</option>
                                <option value="missable_trophies">Missables</option>
                            </select>
                            <button
                                @click="toggleSortOrder"
                                class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
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
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No games found</h3>
                        <p class="text-gray-500 dark:text-gray-400">Try adjusting your filters</p>
                    </div>

                    <!-- Games List -->
                    <div v-else class="space-y-4">
                        <GameCard
                            v-for="game in games"
                            :key="game.id"
                            :game="game"
                            @click="openGame(game)"
                        />
                    </div>

                    <!-- Load More / Pagination -->
                    <div v-if="hasMore && games.length > 0" class="mt-8 text-center">
                        <button
                            @click="loadMore"
                            :disabled="loading"
                            class="px-8 py-3 bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 font-medium rounded-xl shadow-sm hover:shadow-md dark:shadow-slate-900/50 transition-all disabled:opacity-50"
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

        <!-- Footer -->
        <footer class="mt-16 border-t border-gray-200 dark:border-slate-700/50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Brand -->
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <div class="w-6 h-6 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-md flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium">MyNextPlat</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">&copy; {{ new Date().getFullYear() }}</span>
                    </div>

                    <!-- Data Sources Attribution -->
                    <div class="flex flex-wrap items-center justify-center gap-x-1 gap-y-0.5 text-xs text-gray-400 dark:text-gray-500">
                        <span>Game data from</span>
                        <a href="https://www.igdb.com" target="_blank" class="text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white font-medium">IGDB.com</a>
                        <span class="hidden sm:inline">&middot;</span>
                        <span class="hidden sm:inline">Guides from</span>
                        <span class="sm:hidden w-full text-center">Guides from</span>
                        <a href="https://psnprofiles.com" target="_blank" class="text-blue-500 hover:text-blue-400 font-medium">PSNProfiles</a>
                        <span>&middot;</span>
                        <a href="https://www.playstationtrophies.org" target="_blank" class="text-purple-500 hover:text-purple-400 font-medium">PS Trophies</a>
                        <span>&middot;</span>
                        <a href="https://www.powerpyx.com" target="_blank" class="text-orange-500 hover:text-orange-400 font-medium">PowerPyx</a>
                    </div>

                    <!-- Links -->
                    <div class="flex items-center gap-4 text-xs text-gray-400 dark:text-gray-500">
                        <span>Made for trophy hunters</span>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Mobile Filters Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showMobileFilters"
                    class="fixed inset-0 z-50 lg:hidden"
                >
                    <div class="absolute inset-0 bg-black/50" @click="showMobileFilters = false"></div>
                    <div class="absolute inset-y-0 right-0 w-full max-w-sm bg-gray-50 dark:bg-slate-900 shadow-xl overflow-y-auto">
                        <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-100 dark:border-slate-700 px-4 py-3 flex items-center justify-between">
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
                        <div class="p-4 pb-24">
                            <GameFilters @update:filters="onFilterChange" />
                        </div>
                        <!-- Sticky footer with Show Results button -->
                        <div class="sticky bottom-0 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700 p-4">
                            <button
                                @click="showMobileFilters = false"
                                class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors"
                            >
                                Show {{ total.toLocaleString() }} Results
                            </button>
                        </div>
                    </div>
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
                        <div class="w-16 h-16 mx-auto mb-4 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import GameCard from '../components/GameCard.vue'
import GameFilters from '../components/GameFilters.vue'
import { useAuth } from '../composables/useAuth'

// SEO Meta Tags
useHead({
    title: 'MyNextPlat - PlayStation Trophy Guides & Tracker',
    meta: [
        { name: 'description', content: 'Find your next platinum trophy. Browse PlayStation trophy guides from PSNProfiles, PlayStationTrophies, and PowerPyx. Filter by difficulty, time, and more.' },
        { property: 'og:title', content: 'MyNextPlat - PlayStation Trophy Guides & Tracker' },
        { property: 'og:description', content: 'Find your next platinum trophy. Browse PlayStation trophy guides from PSNProfiles, PlayStationTrophies, and PowerPyx.' },
        { property: 'og:type', content: 'website' },
        { name: 'twitter:card', content: 'summary' },
        { name: 'twitter:title', content: 'MyNextPlat - PlayStation Trophy Guides & Tracker' },
        { name: 'twitter:description', content: 'Find your next platinum trophy. Browse PlayStation trophy guides with filters for difficulty, time, and more.' },
        { name: 'keywords', content: 'playstation, trophy guide, platinum trophy, ps5, ps4, psnprofiles, powerpyx, trophy hunting' },
    ],
})

const route = useRoute()
const { user, isAuthenticated, isAdmin, initAuth, loginWithGoogle, logout } = useAuth()

const showUserMenu = ref(false)
const showLoginPrompt = ref(false)
const showViewModeMenu = ref(false)
const viewMode = ref('all') // 'all' or 'library'

// Check for login required query param
watch(() => route.query.login, (val) => {
    if (val === 'required') {
        showLoginPrompt.value = true
        // Clear the query param
        window.history.replaceState({}, '', '/')
    }
}, { immediate: true })

async function handleLogout() {
    await logout()
    showUserMenu.value = false
}

// Close menus when clicking outside
function closeMenus(e) {
    if (!e.target.closest('.user-menu-container')) {
        showUserMenu.value = false
    }
    if (!e.target.closest('.view-mode-menu-container')) {
        showViewModeMenu.value = false
    }
}

onMounted(() => {
    initAuth()
    document.addEventListener('click', closeMenus)
})


const games = ref([])
const loading = ref(true)
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const sortBy = ref('title')
const sortOrder = ref('asc')
const showMobileFilters = ref(false)

const filters = reactive({})

// Dark mode
const darkMode = ref(false)

function initDarkMode() {
    const stored = localStorage.getItem('darkMode')
    if (stored !== null) {
        darkMode.value = stored === 'true'
    } else {
        darkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
    }
    applyDarkMode()
}

function toggleDarkMode() {
    darkMode.value = !darkMode.value
    localStorage.setItem('darkMode', darkMode.value ? 'true' : 'false')
    applyDarkMode()
}

function applyDarkMode() {
    if (darkMode.value) {
        document.documentElement.classList.add('dark')
    } else {
        document.documentElement.classList.remove('dark')
    }
}

const hasMore = computed(() => currentPage.value < lastPage.value)

function onFilterChange(newFilters) {
    Object.assign(filters, newFilters)
    currentPage.value = 1
    games.value = []
    loadGames()
    // Don't auto-close mobile filters - let user adjust multiple filters
}

function toggleSortOrder() {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    currentPage.value = 1
    games.value = []
    loadGames()
}

function openGame(game) {
    // TODO: Navigate to game detail page
    console.log('Open game:', game.slug)
}

function switchViewMode(mode) {
    if (mode === 'library' && !isAuthenticated.value) {
        showLoginPrompt.value = true
        return
    }
    viewMode.value = mode
    currentPage.value = 1
    games.value = []
    loadGames()
}

async function loadGames() {
    loading.value = true

    try {
        const params = new URLSearchParams()

        // My Library filter
        if (viewMode.value === 'library') {
            params.append('my_library', 'true')
        }

        // PSN library filter (game_ids)
        if (filters.game_ids?.length) {
            params.append('game_ids', filters.game_ids.join(','))
            // When PSN library is loaded, respect the user's "has guide" toggle
            // (don't force has_guide=true)
        } else if (viewMode.value === 'all' && !filters.search) {
            // Only show games with guides on the public homepage when browsing (not searching)
            // When searching, show all games so users can find specific titles
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
        if (filters.min_score > 0) params.append('min_score', filters.min_score)
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

        const response = await fetch(`/api/games?${params}`)
        const data = await response.json()

        if (currentPage.value === 1) {
            games.value = data.data
        } else {
            games.value.push(...data.data)
        }

        total.value = data.total
        lastPage.value = data.last_page
    } catch (e) {
        console.error('Failed to load games:', e)
    } finally {
        loading.value = false
    }
}

function loadMore() {
    currentPage.value++
    loadGames()
}

onMounted(() => {
    initDarkMode()
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
</style>
