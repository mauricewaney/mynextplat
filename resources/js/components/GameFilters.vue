<template>
    <div class="space-y-1">
        <!-- Search -->
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                v-model="filters.search"
                type="text"
                placeholder="Search games..."
                class="w-full pl-10 pr-10 py-3 bg-white dark:bg-slate-800 dark:text-gray-200 border border-gray-200 dark:border-slate-600 rounded-xl shadow-sm dark:shadow-none outline-none focus:border-gray-400 dark:focus:border-slate-500 text-sm placeholder-gray-400 dark:placeholder-gray-500"
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

        <!-- Active Filters Summary (always reserve space to prevent layout jump) -->
        <div class="h-8 flex items-center gap-2 flex-wrap">
            <template v-if="activeFilterCount > 0">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ activeFilterCount }} filter{{ activeFilterCount > 1 ? 's' : '' }} active</span>
                <button
                    @click="clearAllFilters"
                    class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-medium"
                >
                    Clear all
                </button>
            </template>
        </div>

        <!-- All Filters -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden">
            <!-- Platforms -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <label class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-2 block">Platforms</label>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="platform in platforms"
                        :key="platform.id"
                        @click="toggleFilter('platform_ids', platform.id)"
                        :class="[
                            'h-10 px-2 rounded-md text-sm font-medium transition-all inline-flex items-center',
                            isSelected('platform_ids', platform.id)
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'
                        ]"
                    >
                        <PlatformIcon :slug="platform.slug" :fallback="platform.short_name" :label="platform.slug === 'ps-vr' ? 'VR' : ''" :size-class="platform.slug === 'ps-vr' ? 'h-5' : 'h-8'" />
                    </button>
                </div>
            </div>

            <!-- IGDB User Score -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">IGDB User Score</span>
                    <span v-if="filters.user_score_min > 0 || filters.user_score_max < 100" class="text-gray-600 dark:text-gray-400">{{ filters.user_score_min }} - {{ filters.user_score_max }}</span>
                    <span v-else class="text-gray-400 dark:text-gray-500">Any</span>
                </div>
                <div class="relative h-2">
                    <div class="absolute inset-0 bg-gray-200 dark:bg-slate-600 rounded-full"></div>
                    <div
                        class="absolute h-full bg-primary-500 rounded-full"
                        :style="{
                            left: `${(filters.user_score_min / 100) * 100}%`,
                            right: `${((100 - filters.user_score_max) / 100) * 100}%`
                        }"
                    ></div>
                    <input
                        type="range"
                        v-model.number="filters.user_score_min"
                        min="0"
                        max="100"
                        step="5"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-primary-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @change="onUserScoreMinChange"
                    />
                    <input
                        type="range"
                        v-model.number="filters.user_score_max"
                        min="0"
                        max="100"
                        step="5"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-primary-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @change="onUserScoreMaxChange"
                    />
                </div>
            </div>

            <!-- IGDB Critic Score -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">IGDB Critic Score</span>
                    <span v-if="filters.critic_score_min > 0 || filters.critic_score_max < 100" class="text-gray-600 dark:text-gray-400">{{ filters.critic_score_min }} - {{ filters.critic_score_max }}</span>
                    <span v-else class="text-gray-400 dark:text-gray-500">Any</span>
                </div>
                <div class="relative h-2">
                    <div class="absolute inset-0 bg-gray-200 dark:bg-slate-600 rounded-full"></div>
                    <div
                        class="absolute h-full bg-primary-500 rounded-full"
                        :style="{
                            left: `${(filters.critic_score_min / 100) * 100}%`,
                            right: `${((100 - filters.critic_score_max) / 100) * 100}%`
                        }"
                    ></div>
                    <input
                        type="range"
                        v-model.number="filters.critic_score_min"
                        min="0"
                        max="100"
                        step="5"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-primary-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @change="onCriticScoreMinChange"
                    />
                    <input
                        type="range"
                        v-model.number="filters.critic_score_max"
                        min="0"
                        max="100"
                        step="5"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-primary-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @change="onCriticScoreMaxChange"
                    />
                </div>
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
                        class="absolute h-full bg-primary-500 rounded-full"
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
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-primary-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @change="onDifficultyMinChange"
                    />
                    <input
                        type="range"
                        v-model.number="filters.difficulty_max"
                        min="1"
                        max="10"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-primary-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @change="onDifficultyMaxChange"
                    />
                </div>
            </div>

            <!-- Has Guide Filter -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <label class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-1.5 block">Guide Availability</label>
                <div class="flex gap-1.5">
                    <button
                        @click="filters.has_guide = null; emitFilters()"
                        :class="[
                            'flex-1 py-1.5 rounded-lg text-sm font-medium transition-all',
                            filters.has_guide === null
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'
                        ]"
                    >
                        Any
                    </button>
                    <button
                        @click="filters.has_guide = true; emitFilters()"
                        :class="[
                            'flex-1 py-1.5 rounded-lg text-sm font-medium transition-all',
                            filters.has_guide === true
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'
                        ]"
                    >
                        Has Guide
                    </button>
                    <button
                        @click="filters.has_guide = false; emitFilters()"
                        :class="[
                            'flex-1 py-1.5 rounded-lg text-sm font-medium transition-all',
                            filters.has_guide === false
                                ? 'bg-amber-600 text-white'
                                : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'
                        ]"
                    >
                        No Guide
                    </button>
                </div>
            </div>

            <!-- Trophy toggles -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <div class="relative shrink-0">
                            <input
                                type="checkbox"
                                v-model="filters.has_platinum"
                                :true-value="true"
                                :false-value="null"
                                class="sr-only peer"
                                @change="emitFilters"
                            />
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer-checked:bg-primary-500 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Has platinum</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer group">
                        <div class="relative shrink-0">
                            <input
                                type="checkbox"
                                v-model="filters.has_online_trophies"
                                :true-value="false"
                                :false-value="null"
                                class="sr-only peer"
                                @change="emitFilters"
                            />
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer-checked:bg-primary-600 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">No online</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer group">
                        <div class="relative shrink-0">
                            <input
                                type="checkbox"
                                v-model="filters.missable_trophies"
                                :true-value="false"
                                :false-value="null"
                                class="sr-only peer"
                                @change="emitFilters"
                            />
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer-checked:bg-primary-600 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">No missables</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer group">
                        <div class="relative shrink-0">
                            <input
                                type="checkbox"
                                v-model="filters.exclude_unobtainable"
                                :true-value="true"
                                :false-value="null"
                                class="sr-only peer"
                                @change="emitFilters"
                            />
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer-checked:bg-primary-600 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                        </div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">No unobtainable</span>
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
                                ? 'bg-primary-600 text-white'
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
                                ? 'bg-primary-600 text-white'
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
                        class="absolute h-full bg-primary-500 rounded-full"
                        :style="{
                            left: `${(timeToIndex(filters.time_min) / timeMaxIndex) * 100}%`,
                            right: `${((timeMaxIndex - timeToIndex(filters.time_max)) / timeMaxIndex) * 100}%`
                        }"
                    ></div>
                    <input
                        type="range"
                        v-model.number="timeMinIndex"
                        min="0"
                        :max="timeMaxIndex"
                        step="1"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-primary-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @change="onTimeMinChange"
                    />
                    <input
                        type="range"
                        v-model.number="timeMaxIndex_"
                        min="0"
                        :max="timeMaxIndex"
                        step="1"
                        class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-md [&::-webkit-slider-thumb]:cursor-pointer [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-primary-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:shadow-md [&::-moz-range-thumb]:cursor-pointer"
                        @change="onTimeMaxChange"
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
                                ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 font-medium'
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
                                class="w-4 h-4 text-primary-600 border-gray-300 dark:border-slate-500 rounded focus:ring-primary-500 dark:bg-slate-600"
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
                        class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 text-xs rounded-full"
                    >
                        {{ getGenreName(genreId) }}
                        <button @click="toggleFilter('genre_ids', genreId)" class="hover:text-primary-900 dark:hover:text-primary-100">
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

            <!-- Tags (multiselect dropdown) — hidden until needed -->
            <div v-if="false" class="px-4 py-3">
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
                                class="w-4 h-4 text-primary-600 border-gray-300 dark:border-slate-500 rounded focus:ring-primary-500 dark:bg-slate-600"
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
                        class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 text-xs rounded-full"
                    >
                        {{ getTagName(tagId) }}
                        <button @click="toggleFilter('tag_ids', tagId)" class="hover:text-primary-900 dark:hover:text-primary-100">
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
import PlatformIcon from './PlatformIcon.vue'

const emit = defineEmits(['update:filters'])

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({})
    }
})

// Load saved filters from sessionStorage
const savedFilters = (() => {
    try {
        const saved = sessionStorage.getItem('gameFilters')
        return saved ? JSON.parse(saved) : {}
    } catch {
        return {}
    }
})()

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
    user_score_min: 0,
    user_score_max: 100,
    critic_score_min: 0,
    critic_score_max: 100,
    has_platinum: true,
    has_online_trophies: null,
    missable_trophies: null,
    has_guide: null, // null = any, true = only with guides, false = only without guides
    exclude_unobtainable: true,
    guide_psnp: false,
    guide_pst: false,
    guide_ppx: false,
    ...savedFilters,
    ...props.modelValue
})

// Static platforms - rarely change
const platforms = [
    { id: 5, short_name: 'PS5', slug: 'ps5' },
    { id: 2, short_name: 'PS4', slug: 'ps4' },
    { id: 1, short_name: 'PS3', slug: 'ps3' },
    { id: 3, short_name: 'Vita', slug: 'ps-vita' },
    { id: 4, short_name: 'VR', slug: 'ps-vr' },
]

const filterOptions = reactive({
    genres: [],
    tags: []
})

const genreDropdownOpen = ref(false)
const tagDropdownOpen = ref(false)
const genreDropdownRef = ref(null)
const tagDropdownRef = ref(null)

const timeSteps = [
    0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50,
    60, 70, 80, 90, 100,
    125, 150, 175, 200,
]
const timeMaxIndex = timeSteps.length - 1

function timeToIndex(val) {
    // Find closest step index
    for (let i = timeSteps.length - 1; i >= 0; i--) {
        if (timeSteps[i] <= val) return i
    }
    return 0
}

function indexToTime(idx) {
    return timeSteps[Math.min(Math.max(0, idx), timeMaxIndex)]
}

const timeMinIndex = computed({
    get: () => timeToIndex(filters.time_min),
    set: (idx) => { filters.time_min = indexToTime(idx) },
})

const timeMaxIndex_ = computed({
    get: () => timeToIndex(filters.time_max),
    set: (idx) => { filters.time_max = indexToTime(idx) },
})

const timePresets = [
    { label: '<10h', min: 0, max: 10 },
    { label: '<25h', min: 0, max: 25 },
    { label: '<50h', min: 0, max: 50 },
    { label: '50h+', min: 50, max: 200 },
]

const activeFilterCount = computed(() => {
    let count = 0
    if (filters.search) count++
    if (filters.platform_ids?.length) count++
    if (filters.genre_ids?.length) count++
    if (filters.tag_ids?.length) count++
    if (filters.difficulty_min > 1 || filters.difficulty_max < 10) count++
    if (filters.time_min > 0 || filters.time_max < 200) count++
    if (filters.max_playthroughs) count++
    if (filters.user_score_min > 0 || filters.user_score_max < 100) count++
    if (filters.critic_score_min > 0 || filters.critic_score_max < 100) count++
    if (filters.has_platinum === true) count++
    if (filters.has_online_trophies === false) count++
    if (filters.missable_trophies === false) count++
    if (filters.exclude_unobtainable === true) count++
    if (filters.has_guide !== null) count++
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

function onUserScoreMinChange() {
    if (filters.user_score_min > filters.user_score_max) {
        filters.user_score_max = filters.user_score_min
    }
    emitFilters()
}

function onUserScoreMaxChange() {
    if (filters.user_score_max < filters.user_score_min) {
        filters.user_score_min = filters.user_score_max
    }
    emitFilters()
}

function onCriticScoreMinChange() {
    if (filters.critic_score_min > filters.critic_score_max) {
        filters.critic_score_max = filters.critic_score_min
    }
    emitFilters()
}

function onCriticScoreMaxChange() {
    if (filters.critic_score_max < filters.critic_score_min) {
        filters.critic_score_min = filters.critic_score_max
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
    filters.search = ''
    filters.platform_ids = []
    filters.genre_ids = []
    filters.tag_ids = []
    filters.difficulty_min = 1
    filters.difficulty_max = 10
    filters.time_min = 0
    filters.time_max = 200
    filters.max_playthroughs = null
    filters.user_score_min = 0
    filters.user_score_max = 100
    filters.critic_score_min = 0
    filters.critic_score_max = 100
    filters.has_platinum = null
    filters.has_online_trophies = null
    filters.missable_trophies = null
    filters.exclude_unobtainable = null
    filters.has_guide = null
    filters.guide_psnp = false
    filters.guide_pst = false
    filters.guide_ppx = false

    // Clear saved filters from sessionStorage
    sessionStorage.removeItem('gameFilters')

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
        filterOptions.genres = data.genres || []
        filterOptions.tags = data.tags || []
    } catch (e) {
        console.error('Failed to load filter options:', e)
    }
}

onMounted(() => {
    loadFilterOptions()
    document.addEventListener('click', handleClickOutside)
    // Emit initial filters to apply any saved state
    emitFilters()
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
