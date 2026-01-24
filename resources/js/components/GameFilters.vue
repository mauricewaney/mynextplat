<template>
    <div class="space-y-1">
        <!-- PSN Library Lookup -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden mb-3">
            <div class="px-4 py-3">
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300 mb-3">
                    <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5m-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6m6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5m-6 8h1.5v1.5H13V13m1.5 1.5H16V16h-1.5v-1.5M16 13h1.5v1.5H16V13m-3 3h1.5v1.5H13V16m1.5 1.5H16V19h-1.5v-1.5M16 16h1.5v1.5H16V16m1.5-1.5H19V16h-1.5v-1.5m0 3H19V19h-1.5v-1.5M19 13h-1.5v1.5H19V13"/>
                    </svg>
                    <span class="font-medium text-sm">My PSN Library</span>
                </div>

                <!-- User not loaded: show input -->
                <div v-if="!psnUser">
                    <!-- My Library button -->
                    <button
                        @click="loadMyLibrary"
                        :disabled="psnLoading"
                        class="w-full mb-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
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

                    <form @submit.prevent="lookupPSN" class="flex gap-2">
                        <input
                            v-model="psnUsername"
                            type="text"
                            placeholder="Enter PSN username..."
                            class="flex-1 px-3 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-sm dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-gray-400 dark:placeholder-gray-500"
                            :disabled="psnLoading"
                        />
                        <button
                            type="submit"
                            :disabled="psnLoading || !psnUsername.trim()"
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

                    <!-- PSN Filter Toggles -->
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-700 space-y-3">
                        <label class="flex items-center justify-between cursor-pointer">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Has guide</span>
                            <div class="relative" @click="togglePsnFilter('hasGuide')">
                                <div :class="[
                                    'w-9 h-5 rounded-full transition-colors',
                                    psnHasGuideOnly ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-slate-600'
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
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                v-model="filters.search"
                type="text"
                placeholder="Search games..."
                class="w-full pl-10 pr-10 py-3 bg-white dark:bg-slate-800 dark:text-gray-200 border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500 text-sm placeholder-gray-400 dark:placeholder-gray-500"
                @input="debouncedEmit"
            />
            <button
                v-if="filters.search"
                @click="clearSearch"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Active Filters Summary -->
        <div v-if="activeFilterCount > 0" class="flex items-center gap-2 flex-wrap py-2">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ activeFilterCount }} filter{{ activeFilterCount > 1 ? 's' : '' }} active</span>
            <button
                @click="clearAllFilters"
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium"
            >
                Clear all
            </button>
        </div>

        <!-- All Filters -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden">
            <!-- Platforms -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <label class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-2 block">Platforms</label>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="platform in filterOptions.platforms"
                        :key="platform.id"
                        @click="toggleFilter('platform_ids', platform.id)"
                        :class="[
                            'px-2.5 py-1 rounded-md text-sm font-medium transition-all',
                            isSelected('platform_ids', platform.id)
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'
                        ]"
                    >
                        {{ platform.short_name || platform.name }}
                    </button>
                </div>
            </div>

            <!-- Minimum Score -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">Minimum Score</span>
                    <span v-if="filters.min_score > 0" class="text-indigo-600 dark:text-indigo-400 font-medium">{{ filters.min_score }}+</span>
                    <span v-else class="text-gray-400 dark:text-gray-500">Any</span>
                </div>
                <input
                    type="range"
                    v-model.number="filters.min_score"
                    min="0"
                    max="100"
                    step="5"
                    class="w-full accent-indigo-600"
                    @input="emitFilters"
                />
            </div>

            <!-- Difficulty -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">Difficulty</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ filters.difficulty_min }} - {{ filters.difficulty_max }}</span>
                </div>
                <div class="relative h-2">
                    <div class="absolute inset-0 bg-gray-200 dark:bg-slate-600 rounded-full"></div>
                    <div
                        class="absolute h-full bg-gradient-to-r from-emerald-400 via-yellow-400 to-red-500 rounded-full"
                        :style="{
                            left: `${((filters.difficulty_min - 1) / 9) * 100}%`,
                            right: `${((10 - filters.difficulty_max) / 9) * 100}%`
                        }"
                    ></div>
                    <input
                        type="range"
                        v-model.number="filters.difficulty_min"
                        min="1"
                        max="10"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-indigo-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-indigo-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @input="onDifficultyMinChange"
                    />
                    <input
                        type="range"
                        v-model.number="filters.difficulty_max"
                        min="1"
                        max="10"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-indigo-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-indigo-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @input="onDifficultyMaxChange"
                    />
                </div>
            </div>

            <!-- Trophy toggles -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer group flex-1">
                        <div class="relative">
                            <input
                                type="checkbox"
                                v-model="filters.has_online_trophies"
                                :true-value="false"
                                :false-value="null"
                                class="sr-only peer"
                                @change="emitFilters"
                            />
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer-checked:bg-indigo-600 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">No online</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer group flex-1">
                        <div class="relative">
                            <input
                                type="checkbox"
                                v-model="filters.missable_trophies"
                                :true-value="false"
                                :false-value="null"
                                class="sr-only peer"
                                @change="emitFilters"
                            />
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer-checked:bg-indigo-600 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">No missables</span>
                    </label>
                </div>
            </div>

            <!-- Playthroughs -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <label class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-1.5 block">Max playthroughs</label>
                <div class="flex gap-1.5">
                    <button
                        v-for="n in [1, 2, 3]"
                        :key="n"
                        @click="filters.max_playthroughs = filters.max_playthroughs === n ? null : n; emitFilters()"
                        :class="[
                            'flex-1 py-1.5 rounded-lg text-sm font-medium transition-all',
                            filters.max_playthroughs === n
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'
                        ]"
                    >
                        {{ n }}x
                    </button>
                    <button
                        @click="filters.max_playthroughs = null; emitFilters()"
                        :class="[
                            'flex-1 py-1.5 rounded-lg text-sm font-medium transition-all',
                            !filters.max_playthroughs
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'
                        ]"
                    >
                        Any
                    </button>
                </div>
            </div>

            <!-- Time to Platinum -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">Time to Platinum</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ filters.time_min }}h - {{ filters.time_max >= 200 ? '200+' : filters.time_max }}h</span>
                </div>
                <div class="relative h-2">
                    <div class="absolute inset-0 bg-gray-200 dark:bg-slate-600 rounded-full"></div>
                    <div
                        class="absolute h-full bg-gradient-to-r from-emerald-400 to-orange-500 rounded-full"
                        :style="{
                            left: `${(filters.time_min / 200) * 100}%`,
                            right: `${((200 - filters.time_max) / 200) * 100}%`
                        }"
                    ></div>
                    <input
                        type="range"
                        v-model.number="filters.time_min"
                        min="0"
                        max="200"
                        step="5"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-indigo-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-indigo-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @input="onTimeMinChange"
                    />
                    <input
                        type="range"
                        v-model.number="filters.time_max"
                        min="0"
                        max="200"
                        step="5"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-indigo-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-indigo-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @input="onTimeMaxChange"
                    />
                </div>
                <div class="flex gap-1.5 mt-2">
                    <button
                        v-for="preset in timePresets"
                        :key="preset.label"
                        @click="setTimePreset(preset)"
                        :class="[
                            'px-2 py-1 rounded-md text-xs transition-all',
                            isTimePresetActive(preset)
                                ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-medium'
                                : 'bg-gray-50 dark:bg-slate-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-600'
                        ]"
                    >
                        {{ preset.label }}
                    </button>
                </div>
            </div>

            <!-- Genres (multiselect dropdown) -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <label class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-1.5 block">Genres</label>
                <div class="relative" ref="genreDropdownRef">
                    <button
                        @click="genreDropdownOpen = !genreDropdownOpen"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-sm text-left flex items-center justify-between hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors"
                    >
                        <span v-if="filters.genre_ids?.length" class="text-gray-900 dark:text-gray-200">
                            {{ filters.genre_ids.length }} selected
                        </span>
                        <span v-else class="text-gray-400 dark:text-gray-500">Select genres...</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div
                        v-if="genreDropdownOpen"
                        class="absolute z-20 mt-1 w-full bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                    >
                        <label
                            v-for="genre in filterOptions.genres"
                            :key="genre.id"
                            class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 dark:hover:bg-slate-600 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :checked="isSelected('genre_ids', genre.id)"
                                @change="toggleFilter('genre_ids', genre.id)"
                                class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-slate-500 rounded focus:ring-indigo-500 dark:bg-slate-600"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ genre.name }}</span>
                        </label>
                    </div>
                </div>
                <!-- Selected genres chips -->
                <div v-if="filters.genre_ids?.length" class="flex flex-wrap gap-1 mt-2">
                    <span
                        v-for="genreId in filters.genre_ids"
                        :key="genreId"
                        class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs rounded-full"
                    >
                        {{ getGenreName(genreId) }}
                        <button @click="toggleFilter('genre_ids', genreId)" class="hover:text-indigo-900 dark:hover:text-indigo-100">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </span>
                </div>
            </div>

            <!-- Guide Sources -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <label class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-1.5 block">Guide Sources</label>
                <div class="flex gap-1.5">
                    <button
                        @click="filters.guide_psnp = !filters.guide_psnp; emitFilters()"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
                            filters.guide_psnp
                                ? 'bg-blue-600 text-white'
                                : 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50'
                        ]"
                    >
                        PSNP
                    </button>
                    <button
                        @click="filters.guide_pst = !filters.guide_pst; emitFilters()"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
                            filters.guide_pst
                                ? 'bg-purple-600 text-white'
                                : 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 hover:bg-purple-100 dark:hover:bg-purple-900/50'
                        ]"
                    >
                        PST
                    </button>
                    <button
                        @click="filters.guide_ppx = !filters.guide_ppx; emitFilters()"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
                            filters.guide_ppx
                                ? 'bg-orange-600 text-white'
                                : 'bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 hover:bg-orange-100 dark:hover:bg-orange-900/50'
                        ]"
                    >
                        PPX
                    </button>
                </div>
            </div>

            <!-- Tags (multiselect dropdown) -->
            <div class="px-4 py-3">
                <label class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-1.5 block">Tags</label>
                <div class="relative" ref="tagDropdownRef">
                    <button
                        @click="tagDropdownOpen = !tagDropdownOpen"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-sm text-left flex items-center justify-between hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors"
                    >
                        <span v-if="filters.tag_ids?.length" class="text-gray-900 dark:text-gray-200">
                            {{ filters.tag_ids.length }} selected
                        </span>
                        <span v-else class="text-gray-400 dark:text-gray-500">Select tags...</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div
                        v-if="tagDropdownOpen"
                        class="absolute z-20 mt-1 w-full bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                    >
                        <label
                            v-for="tag in filterOptions.tags"
                            :key="tag.id"
                            class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 dark:hover:bg-slate-600 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :checked="isSelected('tag_ids', tag.id)"
                                @change="toggleFilter('tag_ids', tag.id)"
                                class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-slate-500 rounded focus:ring-indigo-500 dark:bg-slate-600"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ tag.name }}</span>
                        </label>
                    </div>
                </div>
                <!-- Selected tags chips -->
                <div v-if="filters.tag_ids?.length" class="flex flex-wrap gap-1 mt-2">
                    <span
                        v-for="tagId in filters.tag_ids"
                        :key="tagId"
                        class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs rounded-full"
                    >
                        {{ getTagName(tagId) }}
                        <button @click="toggleFilter('tag_ids', tagId)" class="hover:text-indigo-900 dark:hover:text-indigo-100">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'

const emit = defineEmits(['update:filters'])

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({})
    }
})

const filters = reactive({
    search: '',
    platform_ids: [],
    genre_ids: [],
    tag_ids: [],
    difficulty_min: 1,
    difficulty_max: 10,
    time_min: 0,
    time_max: 200,
    max_playthroughs: null,
    min_score: 0,
    has_online_trophies: null,
    missable_trophies: null,
    guide_psnp: false,
    guide_pst: false,
    guide_ppx: false,
    game_ids: null, // PSN library filter
    ...props.modelValue
})

// PSN Lookup state
const psnUsername = ref('')
const psnLoading = ref(false)
const psnError = ref('')
const psnUser = ref(null)
const psnStats = ref({ total_psn_games: 0, matched_games: 0, unmatched_games: 0, has_guide: 0 })
const psnAllGameIds = ref([])
const psnHasGuideIds = ref([])
const psnHasGuideOnly = ref(true) // Default to showing only games with guides

const filterOptions = reactive({
    platforms: [],
    genres: [],
    tags: []
})

const genreDropdownOpen = ref(false)
const tagDropdownOpen = ref(false)
const genreDropdownRef = ref(null)
const tagDropdownRef = ref(null)

const timePresets = [
    { label: '<10h', min: 0, max: 10 },
    { label: '<25h', min: 0, max: 25 },
    { label: '<50h', min: 0, max: 50 },
    { label: '50h+', min: 50, max: 200 },
]

const activeFilterCount = computed(() => {
    let count = 0
    if (filters.game_ids?.length) count++ // PSN library filter
    if (filters.search) count++
    if (filters.platform_ids?.length) count++
    if (filters.genre_ids?.length) count++
    if (filters.tag_ids?.length) count++
    if (filters.difficulty_min > 1 || filters.difficulty_max < 10) count++
    if (filters.time_min > 0 || filters.time_max < 200) count++
    if (filters.max_playthroughs) count++
    if (filters.min_score > 0) count++
    if (filters.has_online_trophies === false) count++
    if (filters.missable_trophies === false) count++
    if (filters.guide_psnp || filters.guide_pst || filters.guide_ppx) count++
    return count
})

const guideSourceCount = computed(() => {
    let count = 0
    if (filters.guide_psnp) count++
    if (filters.guide_pst) count++
    if (filters.guide_ppx) count++
    return count
})

function getGenreName(id) {
    return filterOptions.genres.find(g => g.id === id)?.name || ''
}

function getTagName(id) {
    return filterOptions.tags.find(t => t.id === id)?.name || ''
}

function clearSearch() {
    filters.search = ''
    emitFilters()
}

async function loadMyLibrary() {
    psnLoading.value = true
    psnError.value = ''

    try {
        const response = await fetch('/api/psn/my-owned-games')
        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.message || 'Failed to load library')
        }

        psnUser.value = data.user
        psnAllGameIds.value = data.game_ids
        psnHasGuideIds.value = data.has_guide_ids || []
        psnStats.value = {
            total_psn_games: data.stats.total_owned_games,
            matched_games: data.stats.matched_games,
            unmatched_games: data.stats.unmatched_games,
            has_guide: data.stats.has_guide,
        }
        psnHasGuideOnly.value = true
        applyPsnFilters()
    } catch (e) {
        psnError.value = e.message
    } finally {
        psnLoading.value = false
    }
}

async function lookupPSN() {
    if (!psnUsername.value.trim()) return

    psnLoading.value = true
    psnError.value = ''

    try {
        const response = await fetch(`/api/psn/lookup/${encodeURIComponent(psnUsername.value.trim())}`)
        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.message || 'Failed to lookup user')
        }

        psnUser.value = data.user
        psnAllGameIds.value = data.game_ids
        psnHasGuideIds.value = data.has_guide_ids || []
        psnStats.value = {
            total_psn_games: data.stats.total_psn_games,
            matched_games: data.stats.matched_games,
            unmatched_games: data.stats.unmatched_games,
            has_guide: data.stats.has_guide,
        }
        psnHasGuideOnly.value = true
        applyPsnFilters()
    } catch (e) {
        psnError.value = e.message
    } finally {
        psnLoading.value = false
    }
}

function togglePsnFilter(filterType) {
    if (filterType === 'hasGuide') {
        psnHasGuideOnly.value = !psnHasGuideOnly.value
    }
    applyPsnFilters()
}

function applyPsnFilters() {
    let gameIds = psnAllGameIds.value

    if (psnHasGuideOnly.value) {
        gameIds = psnHasGuideIds.value
    }

    filters.game_ids = gameIds
    emitFilters()
}

const psnFilteredCount = computed(() => {
    if (psnHasGuideOnly.value) {
        return psnStats.value.has_guide
    }
    return psnStats.value.matched_games
})

function clearPSN() {
    psnUser.value = null
    psnStats.value = { total_psn_games: 0, matched_games: 0, unmatched_games: 0, has_guide: 0 }
    psnAllGameIds.value = []
    psnHasGuideIds.value = []
    psnHasGuideOnly.value = true
    psnUsername.value = ''
    filters.game_ids = null
    emitFilters()
}

function toggleFilter(key, id) {
    if (!filters[key]) filters[key] = []
    const index = filters[key].indexOf(id)
    if (index > -1) {
        filters[key].splice(index, 1)
    } else {
        filters[key].push(id)
    }
    emitFilters()
}

function isSelected(key, id) {
    return filters[key]?.includes(id)
}

function onDifficultyMinChange() {
    if (filters.difficulty_min > filters.difficulty_max) {
        filters.difficulty_max = filters.difficulty_min
    }
    emitFilters()
}

function onDifficultyMaxChange() {
    if (filters.difficulty_max < filters.difficulty_min) {
        filters.difficulty_min = filters.difficulty_max
    }
    emitFilters()
}

function onTimeMinChange() {
    if (filters.time_min > filters.time_max) {
        filters.time_max = filters.time_min
    }
    emitFilters()
}

function onTimeMaxChange() {
    if (filters.time_max < filters.time_min) {
        filters.time_min = filters.time_max
    }
    emitFilters()
}

function setTimePreset(preset) {
    filters.time_min = preset.min
    filters.time_max = preset.max
    emitFilters()
}

function isTimePresetActive(preset) {
    return filters.time_min === preset.min && filters.time_max === preset.max
}

function clearAllFilters() {
    // Clear all filters except PSN library
    filters.search = ''
    filters.platform_ids = []
    filters.genre_ids = []
    filters.tag_ids = []
    filters.difficulty_min = 1
    filters.difficulty_max = 10
    filters.time_min = 0
    filters.time_max = 200
    filters.max_playthroughs = null
    filters.min_score = 0
    filters.has_online_trophies = null
    filters.missable_trophies = null
    filters.guide_psnp = false
    filters.guide_pst = false
    filters.guide_ppx = false

    // Keep PSN library filter if loaded, reset to default view
    if (psnUser.value) {
        psnHasGuideOnly.value = true
        filters.game_ids = psnHasGuideIds.value
    } else {
        filters.game_ids = null
    }

    emitFilters()
}

let debounceTimer = null
function debouncedEmit() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(emitFilters, 300)
}

function emitFilters() {
    emit('update:filters', { ...filters })
}

// Close dropdowns when clicking outside
function handleClickOutside(event) {
    if (genreDropdownRef.value && !genreDropdownRef.value.contains(event.target)) {
        genreDropdownOpen.value = false
    }
    if (tagDropdownRef.value && !tagDropdownRef.value.contains(event.target)) {
        tagDropdownOpen.value = false
    }
}

async function loadFilterOptions() {
    try {
        const response = await fetch('/api/games/filters')
        const data = await response.json()
        filterOptions.platforms = data.platforms || []
        filterOptions.genres = data.genres || []
        filterOptions.tags = data.tags || []
    } catch (e) {
        console.error('Failed to load filter options:', e)
    }
}

onMounted(() => {
    loadFilterOptions()
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
