<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-primary-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 transition-colors duration-300">
        <!-- Header -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-lg border-b border-gray-200 shadow-sm dark:bg-slate-900/95 dark:border-slate-700/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-4">
                        <router-link to="/" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h1 class="hidden sm:block text-xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 dark:from-primary-400 dark:to-purple-400 bg-clip-text text-transparent">
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
                                            ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400'
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
                                            ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400'
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

                        <!-- PSN Library - Desktop (inline) -->
                        <div class="hidden lg:flex items-center gap-2 border-l border-gray-200 dark:border-slate-700 pl-4 ml-2">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5m-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6m6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5m-6 8h1.5v1.5H13V13m1.5 1.5H16V16h-1.5v-1.5M16 13h1.5v1.5H16V13m-3 3h1.5v1.5H13V16m1.5 1.5H16V19h-1.5v-1.5M16 16h1.5v1.5H16V16m1.5-1.5H19V16h-1.5v-1.5m0 3H19V19h-1.5v-1.5M19 13h-1.5v1.5H19V13"/>
                            </svg>
                            <!-- Not loaded: show search form -->
                            <template v-if="!isPsnLoaded">
                                <form @submit.prevent="handlePsnLookup" class="flex items-center gap-2">
                                    <input
                                        v-model="psnUsernameInput"
                                        type="text"
                                        placeholder="PSN username..."
                                        class="w-32 px-2 py-1 bg-gray-100 dark:bg-slate-700 border-0 rounded-md text-sm dark:text-gray-200 focus:ring-2 focus:ring-primary-500 placeholder-gray-400 dark:placeholder-gray-500"
                                        :disabled="psnLoading"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="psnLoading || !psnUsernameInput.trim()"
                                        class="px-2 py-1 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-xs font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        {{ psnLoading ? '...' : 'Load' }}
                                    </button>
                                </form>
                                <button
                                    v-if="isAdmin"
                                    @click="loadMyPsnLibrary"
                                    :disabled="psnLoading"
                                    class="px-2 py-1 text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium disabled:opacity-50"
                                >
                                    My Library
                                </button>
                            </template>
                            <!-- Loaded: show user info -->
                            <template v-else>
                                <div class="flex items-center gap-2">
                                    <img
                                        v-if="psnUser?.avatar"
                                        :src="psnUser.avatar"
                                        :alt="psnUser.username"
                                        class="w-6 h-6 rounded-full"
                                    />
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ psnUser?.username }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ psnStats.has_guide }} with guide)</span>
                                    <button
                                        @click="clearPsnLibrary"
                                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded transition-colors"
                                        title="Clear"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <p v-if="psnError" class="text-xs text-red-500">{{ psnError }}</p>
                        </div>
                    </div>

                    <!-- Mobile actions -->
                    <div class="lg:hidden flex items-center gap-1">
                        <!-- Mobile PSN Library Button -->
                        <button
                            @click="showMobilePsnPanel = true"
                            :class="[
                                'p-2 rounded-lg transition-colors',
                                isPsnLoaded
                                    ? 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800'
                            ]"
                            title="PSN Library"
                        >
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5m-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6m6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5m-6 8h1.5v1.5H13V13m1.5 1.5H16V16h-1.5v-1.5M16 13h1.5v1.5H16V13m-3 3h1.5v1.5H13V16m1.5 1.5H16V19h-1.5v-1.5M16 16h1.5v1.5H16V16m1.5-1.5H19V16h-1.5v-1.5m0 3H19V19h-1.5v-1.5M19 13h-1.5v1.5H19V13"/>
                            </svg>
                        </button>
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
                            <div v-else class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-medium aspect-square shrink-0">
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

                    <!-- Desktop actions -->
                    <div class="hidden lg:flex items-center gap-4 text-sm">
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
                                <div v-else class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-medium">
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
                    <!-- PSN Library Banner (Mobile/Tablet - when loaded) -->
                    <div v-if="isPsnLoaded" class="lg:hidden mb-4 bg-primary-50 dark:bg-primary-900/30 rounded-xl p-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="psnUser?.avatar"
                                    :src="psnUser.avatar"
                                    :alt="psnUser.username"
                                    class="w-8 h-8 rounded-full"
                                />
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ psnUser?.username }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ psnStats.matched_games }} matched &middot; {{ psnStats.has_guide }} with guide
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Guide only</span>
                                    <div class="relative" @click="toggleHasGuideOnly">
                                        <div :class="[
                                            'w-8 h-4 rounded-full transition-colors',
                                            psnHasGuideOnly ? 'bg-primary-600' : 'bg-gray-300 dark:bg-slate-600'
                                        ]"></div>
                                        <div :class="[
                                            'absolute top-0.5 w-3 h-3 bg-white rounded-full transition-transform shadow-sm',
                                            psnHasGuideOnly ? 'left-4' : 'left-0.5'
                                        ]"></div>
                                    </div>
                                </label>
                                <button
                                    @click="clearPsnLibrary"
                                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sort Bar (All screen sizes) -->
                    <div class="flex items-center justify-between mb-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm p-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ total.toLocaleString() }}</span> games
                        </span>
                        <div class="flex items-center gap-2">
                            <label class="hidden sm:block text-sm text-gray-500 dark:text-gray-400">Sort:</label>
                            <select
                                v-model="sortBy"
                                @change="loadGames"
                                class="border-0 bg-gray-100 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-primary-500"
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

        <!-- Footer -->
        <footer class="mt-16 border-t border-gray-200 dark:border-slate-700/50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Brand -->
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <div class="w-6 h-6 bg-gradient-to-br from-primary-500 to-purple-600 rounded-md flex items-center justify-center">
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

                    <!-- Contact & Links -->
                    <div class="flex items-center gap-4 text-xs text-gray-400 dark:text-gray-500">
                        <a href="mailto:contact@mynextplat.com" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            contact@mynextplat.com
                        </a>
                        <span class="hidden sm:inline">&middot;</span>
                        <span class="hidden sm:inline">Made for trophy hunters</span>
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
                                class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
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

        <!-- Mobile PSN Search Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showMobilePsnPanel && !isPsnLoaded"
                    class="fixed inset-0 z-50 lg:hidden flex items-start justify-center pt-16"
                >
                    <div class="absolute inset-0 bg-black/50" @click="showMobilePsnPanel = false"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl w-[calc(100%-2rem)] max-w-sm p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5m-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6m6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5m-6 8h1.5v1.5H13V13m1.5 1.5H16V16h-1.5v-1.5M16 13h1.5v1.5H16V13m-3 3h1.5v1.5H13V16m1.5 1.5H16V19h-1.5v-1.5M16 16h1.5v1.5H16V16m1.5-1.5H19V16h-1.5v-1.5m0 3H19V19h-1.5v-1.5M19 13h-1.5v1.5H19V13"/>
                            </svg>
                            <h2 class="font-semibold text-gray-900 dark:text-white">Load PSN Library</h2>
                        </div>
                        <form @submit.prevent="handlePsnLookup(); showMobilePsnPanel = false" class="space-y-3">
                            <input
                                v-model="psnUsernameInput"
                                type="text"
                                placeholder="Enter PSN username..."
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-sm dark:text-gray-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 placeholder-gray-400 dark:placeholder-gray-500"
                                :disabled="psnLoading"
                            />
                            <div class="flex gap-2">
                                <button
                                    type="submit"
                                    :disabled="psnLoading || !psnUsernameInput.trim()"
                                    class="flex-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    {{ psnLoading ? 'Loading...' : 'Load' }}
                                </button>
                                <button
                                    type="button"
                                    @click="showMobilePsnPanel = false"
                                    class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                        <button
                            v-if="isAdmin"
                            @click="loadMyPsnLibrary(); showMobilePsnPanel = false"
                            :disabled="psnLoading"
                            class="w-full mt-2 px-4 py-2 text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium disabled:opacity-50"
                        >
                            Load My Library
                        </button>
                        <p v-if="psnError" class="mt-2 text-xs text-red-500 dark:text-red-400">{{ psnError }}</p>
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
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import GameCard from '../components/GameCard.vue'
import GameFilters from '../components/GameFilters.vue'
import { useAuth } from '../composables/useAuth'
import { usePSNLibrary } from '../composables/usePSNLibrary'

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
const {
    psnGameIds,
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

const psnUsernameInput = ref('')

function handlePsnLookup() {
    if (psnUsernameInput.value.trim()) {
        lookupPSN(psnUsernameInput.value.trim())
    }
}

function loadMyPsnLibrary() {
    loadMyLibrary()
}

function clearPsnLibrary() {
    clearPSN()
    psnUsernameInput.value = ''
}

const showUserMenu = ref(false)
const showPsnPanel = ref(false)
const showMobilePsnPanel = ref(false)
const showUnmatchedModal = ref(false)
const unmatchedSearch = ref('')
const showLoginPrompt = ref(false)
const showViewModeMenu = ref(false)
const viewMode = ref(sessionStorage.getItem('viewMode') || 'all') // 'all' or 'library'

// Check for login required query param
watch(() => route.query.login, (val) => {
    if (val === 'required') {
        showLoginPrompt.value = true
        // Clear the query param
        window.history.replaceState({}, '', '/')
    }
}, { immediate: true })

// Watch PSN game IDs and reload games when they change
watch(psnGameIds, () => {
    currentPage.value = 1
    games.value = []
    loadGames()
})

// Close mobile PSN panel when PSN loads
watch(isPsnLoaded, (loaded) => {
    if (loaded) {
        showMobilePsnPanel.value = false
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
    if (!e.target.closest('.psn-panel-container')) {
        showPsnPanel.value = false
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
// Load saved state from sessionStorage or use defaults
const sortBy = ref(sessionStorage.getItem('sortBy') || 'critic_score')
const sortOrder = ref(sessionStorage.getItem('sortOrder') || 'desc')
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
    // Save filters to sessionStorage (exclude game_ids as they're from PSN lookup)
    const filtersToSave = { ...newFilters }
    delete filtersToSave.game_ids
    sessionStorage.setItem('homeFilters', JSON.stringify(filtersToSave))
    currentPage.value = 1
    games.value = []
    loadGames()
    // Don't auto-close mobile filters - let user adjust multiple filters
}

function toggleSortOrder() {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    sessionStorage.setItem('sortOrder', sortOrder.value)
    currentPage.value = 1
    games.value = []
    loadGames()
}

// Watch sortBy changes to save to sessionStorage
watch(sortBy, (newVal) => {
    sessionStorage.setItem('sortBy', newVal)
})

function switchViewMode(mode) {
    if (mode === 'library' && !isAuthenticated.value) {
        showLoginPrompt.value = true
        return
    }
    viewMode.value = mode
    sessionStorage.setItem('viewMode', mode)
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

        // PSN library filter (game_ids from composable)
        if (psnGameIds.value?.length) {
            params.append('game_ids', psnGameIds.value.join(','))
        }

        // Has guide filter - use explicit filter if set, otherwise default to showing guides only for 'all' mode
        if (filters.has_guide === true) {
            params.append('has_guide', 'true')
        } else if (filters.has_guide === false) {
            params.append('has_guide', 'false')
        } else if (viewMode.value === 'all' && !filters.search && !psnGameIds.value?.length) {
            // Default: show games with guides on homepage when browsing (not searching or PSN library)
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
