<template>
    <div>
        <!-- Loading -->
        <div v-if="loading" class="flex justify-center items-center h-96">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="max-w-4xl mx-auto px-4 py-16 text-center">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Game Not Found</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-8">{{ error }}</p>
            <a href="/" class="text-primary-600 hover:underline">Back to Home</a>
        </div>

        <!-- Game Content -->
        <div v-else-if="game">
            <!-- Hero Banner (desktop: full hero with cover+info overlay; mobile: simple banner) -->
            <div
                v-if="game.banner_url"
                class="relative h-48 lg:h-80 bg-cover bg-center"
                :style="{ backgroundImage: `url(${game.banner_url})` }"
            >
                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/60 to-transparent"></div>

                <!-- Mobile: just title + breadcrumb -->
                <div class="lg:hidden absolute inset-0 flex items-end">
                    <div class="max-w-6xl mx-auto px-4 pb-6 w-full">
                        <nav class="mb-3 text-sm">
                            <a href="/" class="text-gray-300 hover:text-white">Home</a>
                            <span class="mx-2 text-gray-500">/</span>
                            <span class="text-gray-400">{{ game.title }}</span>
                        </nav>
                        <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">
                            {{ game.title }}
                        </h1>
                        <div v-if="game.has_platinum || game.gold_count || game.silver_count || game.bronze_count" class="flex items-center gap-2 mt-2">
                            <span v-if="game.has_platinum" class="inline-flex items-center gap-0.5 text-xs font-bold text-blue-300">
                                <TrophyIcon tier="platinum" size="xs" />1
                            </span>
                            <span v-if="game.gold_count" class="inline-flex items-center gap-0.5 text-xs font-bold text-yellow-400">
                                <TrophyIcon tier="gold" size="xs" />{{ game.gold_count }}
                            </span>
                            <span v-if="game.silver_count" class="inline-flex items-center gap-0.5 text-xs font-bold text-gray-300">
                                <TrophyIcon tier="silver" size="xs" />{{ game.silver_count }}
                            </span>
                            <span v-if="game.bronze_count" class="inline-flex items-center gap-0.5 text-xs font-bold text-amber-400">
                                <TrophyIcon tier="bronze" size="xs" />{{ game.bronze_count }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Desktop: cover + full info overlay -->
                <div class="hidden lg:flex absolute inset-0 items-end">
                    <div class="max-w-6xl mx-auto px-4 pb-8 w-full">
                        <nav class="mb-4 text-sm">
                            <a href="/" class="text-gray-300 hover:text-white">Home</a>
                            <span class="mx-2 text-gray-500">/</span>
                            <span class="text-gray-400">{{ game.title }}</span>
                        </nav>
                        <div class="flex gap-6 items-end">
                            <!-- Cover -->
                            <div class="w-48 shrink-0 -mb-24 ml-4 self-start">
                                <div v-if="game.has_platinum || game.gold_count || game.silver_count || game.bronze_count" class="flex items-center justify-start gap-2 mt-2 mb-6">
                                    <span v-if="game.has_platinum" class="inline-flex items-center gap-1 text-sm font-bold text-blue-300">
                                        <TrophyIcon tier="platinum" size="xs" class="!w-4 !h-4" />1
                                    </span>
                                    <span v-if="game.gold_count" class="inline-flex items-center gap-1 text-sm font-bold text-yellow-400">
                                        <TrophyIcon tier="gold" size="xs" class="!w-4 !h-4" />{{ game.gold_count }}
                                    </span>
                                    <span v-if="game.silver_count" class="inline-flex items-center gap-1 text-sm font-bold text-gray-300">
                                        <TrophyIcon tier="silver" size="xs" class="!w-4 !h-4" />{{ game.silver_count }}
                                    </span>
                                    <span v-if="game.bronze_count" class="inline-flex items-center gap-1 text-sm font-bold text-amber-400">
                                        <TrophyIcon tier="bronze" size="xs" class="!w-4 !h-4" />{{ game.bronze_count }}
                                    </span>
                                </div>
                                <img
                                    v-if="game.cover_url"
                                    :src="game.cover_url"
                                    :alt="game.title + ' cover'"
                                    class="w-full h-auto rounded-lg shadow-2xl ring-1 ring-white/10"
                                />
                                <div v-else class="w-full aspect-[3/4] bg-gray-700 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-400 text-4xl">?</span>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0 pb-1 self-start">
                                <h1 class="text-4xl font-bold text-white drop-shadow-lg mb-2">
                                    {{ game.title }}
                                </h1>

                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <!-- Platforms -->
                                        <div v-if="game.platforms?.length" class="flex flex-wrap gap-2 mb-1.5">
                                            <span
                                                v-for="platform in game.platforms"
                                                :key="platform.id"
                                                class="platform-icon-white h-8 px-2 inline-flex items-center bg-slate-900/80 text-white text-sm font-medium rounded backdrop-blur-sm"
                                            >
                                                <PlatformIcon :slug="platform.slug" :fallback="platform.short_name" :label="platform.slug === 'ps-vr' ? 'VR' : ''" size-class="h-6" />
                                            </span>
                                        </div>

                                        <!-- Developer/Publisher -->
                                        <div class="text-sm text-gray-300">
                                            <span v-if="game.developer">{{ game.developer }}</span>
                                            <span v-if="game.developer && game.publisher"> / </span>
                                            <span v-if="game.publisher">{{ game.publisher }}</span>
                                        </div>
                                    </div>

                                    <!-- Scores -->
                                    <div class="flex gap-3 shrink-0">
                                        <div v-if="displayUserScore !== null" class="group/user relative flex flex-col items-center" @click.stop="toggleScoreTooltip('user')">
                                            <div
                                                :class="[
                                                    'w-12 h-12 rounded-lg flex items-center justify-center font-bold text-white shadow-lg',
                                                    userScoreClass,
                                                    displayUserScore === 'N/A' ? 'text-sm' : 'text-xl'
                                                ]"
                                            >
                                                {{ displayUserScore }}
                                            </div>
                                            <span class="text-[10px] text-gray-300 mt-0.5">User</span>
                                            <div
                                                :class="[
                                                    'absolute bottom-full right-0 mb-1.5 px-2 py-1 whitespace-nowrap bg-gray-800 text-gray-200 text-xs rounded shadow-lg pointer-events-none z-50 transition-opacity duration-150',
                                                    showScoreTooltip === 'user' ? 'opacity-100' : 'opacity-0 hidden group-hover/user:block group-hover/user:opacity-100'
                                                ]"
                                            >
                                                <template v-if="displayUserScore === 'N/A'">Not enough IGDB user ratings</template>
                                                <template v-else>IGDB User Score ({{ game.user_score_count }} ratings)</template>
                                            </div>
                                        </div>
                                        <div v-if="displayCriticScore !== null" class="group/critic relative flex flex-col items-center" @click.stop="toggleScoreTooltip('critic')">
                                            <div
                                                :class="[
                                                    'w-12 h-12 rounded-lg flex items-center justify-center font-bold border-2 shadow-lg',
                                                    criticScoreClass,
                                                    displayCriticScore === 'N/A' ? 'text-sm' : 'text-xl'
                                                ]"
                                            >
                                                {{ displayCriticScore }}
                                            </div>
                                            <span class="text-[10px] text-gray-300 mt-0.5">Critic</span>
                                            <div
                                                :class="[
                                                    'absolute bottom-full right-0 mb-1.5 px-2 py-1 whitespace-nowrap bg-gray-800 text-gray-200 text-xs rounded shadow-lg pointer-events-none z-50 transition-opacity duration-150',
                                                    showScoreTooltip === 'critic' ? 'opacity-100' : 'opacity-0 hidden group-hover/critic:block group-hover/critic:opacity-100'
                                                ]"
                                            >
                                                <template v-if="displayCriticScore === 'N/A'">Not enough IGDB critic reviews</template>
                                                <template v-else>IGDB Critic Score ({{ game.critic_score_count }} sources)</template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div v-if="game.description" class="mt-3">
                                    <p class="text-sm text-gray-300 leading-relaxed line-clamp-3">
                                        {{ game.description }}
                                    </p>
                                    <button
                                        @click="showDescriptionModal = true"
                                        class="text-sm text-primary-400 hover:text-primary-300 mt-1 font-medium"
                                    >
                                        Read More
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4 py-8" :class="game.banner_url ? 'lg:pt-4' : ''">
                <!-- Breadcrumb (only if no banner) -->
                <nav v-if="!game.banner_url" class="mb-6 text-sm">
                    <a href="/" class="text-primary-600 hover:underline">Home</a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ game.title }}</span>
                </nav>

            <!-- Header Section -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg mb-8">
                <!-- Top: Cover + Title/Platforms/Developer (mobile/tablet only when banner exists, always on no-banner) -->
                <div :class="game.banner_url ? 'lg:hidden' : ''" class="flex p-4 sm:p-6 gap-4 sm:gap-6">
                    <!-- Cover Image -->
                    <div class="w-28 sm:w-48 md:w-56 shrink-0" :class="game.banner_url ? 'lg:hidden' : ''">
                        <img
                            v-if="game.cover_url"
                            :src="game.cover_url"
                            :alt="game.title + ' cover'"
                            class="w-full h-auto rounded-lg"
                        />
                        <div v-else class="w-full aspect-[3/4] bg-gray-200 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                            <span class="text-gray-400 text-4xl">?</span>
                        </div>
                    </div>

                    <!-- Info beside cover -->
                    <div class="flex-1 min-w-0 flex flex-col">
                        <h1 v-if="!game.banner_url" class="text-lg sm:text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ game.title }}
                        </h1>
                        <div v-if="!game.banner_url && (game.has_platinum || game.gold_count || game.silver_count || game.bronze_count)" class="flex items-center gap-2 sm:gap-3 mb-2">
                            <span v-if="game.has_platinum" class="inline-flex items-center gap-0.5 sm:gap-1 text-xs sm:text-base font-bold text-blue-300 dark:text-blue-200">
                                <TrophyIcon tier="platinum" size="xs" class="sm:!w-5 sm:!h-5" />1
                            </span>
                            <span v-if="game.gold_count" class="inline-flex items-center gap-0.5 sm:gap-1 text-xs sm:text-base font-bold text-yellow-500 dark:text-yellow-400">
                                <TrophyIcon tier="gold" size="xs" class="sm:!w-5 sm:!h-5" />{{ game.gold_count }}
                            </span>
                            <span v-if="game.silver_count" class="inline-flex items-center gap-0.5 sm:gap-1 text-xs sm:text-base font-bold text-gray-400 dark:text-gray-300">
                                <TrophyIcon tier="silver" size="xs" class="sm:!w-5 sm:!h-5" />{{ game.silver_count }}
                            </span>
                            <span v-if="game.bronze_count" class="inline-flex items-center gap-0.5 sm:gap-1 text-xs sm:text-base font-bold text-amber-700 dark:text-amber-500">
                                <TrophyIcon tier="bronze" size="xs" class="sm:!w-5 sm:!h-5" />{{ game.bronze_count }}
                            </span>
                        </div>

                        <!-- Platforms + Developer / Scores row -->
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div class="min-w-0">
                                <!-- Platforms -->
                                <div v-if="game.platforms?.length" class="flex flex-wrap gap-1.5 sm:gap-2 mb-1.5">
                                    <span
                                        v-for="platform in game.platforms"
                                        :key="platform.id"
                                        class="platform-icon-white h-7 sm:h-10 px-1.5 sm:px-2 inline-flex items-center bg-slate-900 text-white text-xs sm:text-sm font-medium rounded"
                                    >
                                        <PlatformIcon :slug="platform.slug" :fallback="platform.short_name" :label="platform.slug === 'ps-vr' ? 'VR' : ''" size-class="h-5 sm:h-8" />
                                    </span>
                                </div>

                                <!-- Developer/Publisher -->
                                <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                    <span v-if="game.developer">{{ game.developer }}</span>
                                    <span v-if="game.developer && game.publisher"> / </span>
                                    <span v-if="game.publisher">{{ game.publisher }}</span>
                                </div>
                            </div>

                            <!-- Scores -->
                            <div class="flex gap-3 shrink-0">
                                <!-- User Score -->
                                <div v-if="displayUserScore !== null" class="group/user relative flex flex-col items-center" @click.stop="toggleScoreTooltip('user')">
                                    <div
                                        :class="[
                                            'w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center font-bold text-white',
                                            userScoreClass,
                                            displayUserScore === 'N/A' ? 'text-xs sm:text-sm' : 'text-lg sm:text-xl'
                                        ]"
                                    >
                                        {{ displayUserScore }}
                                    </div>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">User</span>
                                    <div
                                        :class="[
                                            'absolute bottom-full right-0 mb-1.5 px-2 py-1 whitespace-nowrap bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 text-xs rounded shadow-lg ring-1 ring-black/5 dark:ring-white/10 pointer-events-none z-50 transition-opacity duration-150',
                                            showScoreTooltip === 'user' ? 'opacity-100' : 'opacity-0 hidden group-hover/user:block group-hover/user:opacity-100'
                                        ]"
                                    >
                                        <template v-if="displayUserScore === 'N/A'">Not enough IGDB user ratings</template>
                                        <template v-else>IGDB User Score ({{ game.user_score_count }} ratings)</template>
                                    </div>
                                </div>
                                <!-- Critic Score -->
                                <div v-if="displayCriticScore !== null" class="group/critic relative flex flex-col items-center" @click.stop="toggleScoreTooltip('critic')">
                                    <div
                                        :class="[
                                            'w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center font-bold border-2',
                                            criticScoreClass,
                                            displayCriticScore === 'N/A' ? 'text-xs sm:text-sm' : 'text-lg sm:text-xl'
                                        ]"
                                    >
                                        {{ displayCriticScore }}
                                    </div>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Critic</span>
                                    <div
                                        :class="[
                                            'absolute bottom-full right-0 mb-1.5 px-2 py-1 whitespace-nowrap bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 text-xs rounded shadow-lg ring-1 ring-black/5 dark:ring-white/10 pointer-events-none z-50 transition-opacity duration-150',
                                            showScoreTooltip === 'critic' ? 'opacity-100' : 'opacity-0 hidden group-hover/critic:block group-hover/critic:opacity-100'
                                        ]"
                                    >
                                        <template v-if="displayCriticScore === 'N/A'">Not enough IGDB critic reviews</template>
                                        <template v-else>IGDB Critic Score ({{ game.critic_score_count }} sources)</template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="game.description">
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-6 sm:line-clamp-none">
                                {{ game.description }}
                            </p>
                            <button
                                @click="showDescriptionModal = true"
                                class="sm:hidden text-xs text-primary-600 dark:text-primary-400 hover:underline mt-1 font-medium"
                            >
                                Read More
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Desktop with banner: spacer so card starts below the overflowing cover with a gap -->
                <div v-if="game.banner_url" class="hidden lg:block h-16 bg-transparent"></div>

                <!-- Bottom: Stats + Guides two-column on desktop -->
                <div class="px-4 sm:px-6 pb-4 sm:pb-6">
                    <div class="flex flex-col lg:flex-row gap-6">
                        <!-- Left column: Stats, warnings, genres, actions -->
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Trophy Stats</h3>
                            <!-- Trophy Stats Grid -->
                            <div v-if="hasGuides" class="grid grid-cols-5 gap-1.5 sm:gap-2 mb-4">
                                <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-1.5 sm:p-2 text-center">
                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
                                        {{ game.difficulty || '?' }}<span class="text-[10px] sm:text-xs font-normal">/10</span>
                                    </div>
                                    <div class="text-[9px] sm:text-[10px] text-gray-500 dark:text-gray-400">Difficulty</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-1.5 sm:p-2 text-center">
                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
                                        {{ game.time_min || '?' }}<span v-if="game.time_max && game.time_max !== game.time_min" class="text-xs">-{{ game.time_max }}</span>
                                    </div>
                                    <div class="text-[9px] sm:text-[10px] text-gray-500 dark:text-gray-400">Hours</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-1.5 sm:p-2 text-center">
                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
                                        {{ game.playthroughs_required ? game.playthroughs_required + '+' : '?' }}
                                    </div>
                                    <div class="text-[9px] sm:text-[10px] text-gray-500 dark:text-gray-400">Playthroughs</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-1.5 sm:p-2 text-center">
                                    <div class="text-base sm:text-lg font-bold" :class="game.missable_trophies ? 'text-red-500' : 'text-green-500'">
                                        {{ game.missable_trophies ? 'Yes' : 'No' }}
                                    </div>
                                    <div class="text-[9px] sm:text-[10px] text-gray-500 dark:text-gray-400">Missables</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-1.5 sm:p-2 text-center">
                                    <div class="text-base sm:text-lg font-bold" :class="game.has_online_trophies ? 'text-red-500' : 'text-green-500'">
                                        {{ game.has_online_trophies ? 'Yes' : game.has_online_trophies === false ? 'No' : '?' }}
                                    </div>
                                    <div class="text-[9px] sm:text-[10px] text-gray-500 dark:text-gray-400">Online</div>
                                </div>
                            </div>
                            <div v-else class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4 mb-4 sm:mb-6 text-center">
                                <svg class="w-6 h-6 mx-auto mb-1.5 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <p class="text-sm text-gray-400 dark:text-gray-500">No trophy guide available yet</p>
                                <p class="text-xs text-gray-300 dark:text-gray-600 mt-0.5">Difficulty, time, and other stats will appear when a guide is added</p>
                            </div>

                            <!-- Server Shutdown Warning -->
                            <div v-if="game.server_shutdown_date && new Date(game.server_shutdown_date) > new Date()" class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-4">
                                <span class="text-amber-700 dark:text-amber-300 text-sm font-medium">
                                    Online servers shutting down on {{ new Date(game.server_shutdown_date).toLocaleDateString() }}
                                </span>
                            </div>
                            <div v-else-if="game.server_shutdown_date && new Date(game.server_shutdown_date) <= new Date()" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-4">
                                <span class="text-red-700 dark:text-red-300 text-sm font-medium">
                                    Servers shut down on {{ new Date(game.server_shutdown_date).toLocaleDateString() }} — platinum is unobtainable
                                </span>
                            </div>

                            <!-- Source -->
                            <div v-if="game.data_source" class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                                Source:
                                <a
                                    v-if="game[game.data_source + '_url']"
                                    :href="game[game.data_source + '_url']"
                                    target="_blank"
                                    rel="noopener"
                                    :class="[
                                        'font-medium hover:underline',
                                        game.data_source === 'playstationtrophies' ? 'text-teal-600 dark:text-teal-400' :
                                        game.data_source === 'powerpyx' ? 'text-amber-600 dark:text-amber-400' :
                                        game.data_source === 'psnprofiles' ? 'text-sky-600 dark:text-sky-400' :
                                        'text-gray-600 dark:text-gray-400'
                                    ]"
                                >
                                    {{ guideLabels[game.data_source] || game.data_source }}
                                </a>
                                <span v-else class="font-medium text-gray-600 dark:text-gray-400">
                                    {{ guideLabels[game.data_source] || game.data_source }}
                                </span>
                            </div>

                            <!-- Genres -->
                            <div v-if="game.genres?.length" class="mb-4">
                                <span class="text-sm text-gray-500 dark:text-gray-400 mr-2">Genres:</span>
                                <span
                                    v-for="(genre, i) in game.genres"
                                    :key="genre.id"
                                    class="text-sm text-gray-700 dark:text-gray-300"
                                >
                                    {{ genre.name }}<span v-if="i < game.genres.length - 1">, </span>
                                </span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-3">
                                <button
                                    @click="toggleList"
                                    :disabled="listLoading"
                                    :class="[
                                        'inline-flex items-center gap-2 px-4 sm:px-5 py-2 sm:py-2.5 rounded-lg font-medium transition-all text-sm sm:text-base',
                                        inList
                                            ? 'bg-primary-600 text-white hover:bg-primary-700'
                                            : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 hover:bg-primary-100 dark:hover:bg-primary-900/50 hover:text-primary-600 dark:hover:text-primary-400',
                                        listLoading ? 'opacity-50 cursor-wait' : ''
                                    ]"
                                >
                                    <svg v-if="listLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else-if="inList" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    <span v-if="!isAuthenticated">Sign in to add</span>
                                    <span v-else-if="inList">In My List</span>
                                    <span v-else>Add to My List</span>
                                </button>
                                <a
                                    :href="`/report-issue?game=${game.slug}`"
                                    class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 sm:py-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors text-sm"
                                    title="Report incorrect information"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span class="hidden sm:inline">Report Issue</span>
                                </a>
                            </div>
                        </div>

                        <!-- Right column: Trophy Guides + Voting (desktop) -->
                        <div v-if="hasGuides" class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Trophy Guides</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="guide in sortedGuides"
                                    :key="guide.key"
                                    class="rounded-lg border border-gray-100 dark:border-slate-700 overflow-hidden"
                                >
                                    <div class="flex items-center gap-2.5 p-3 bg-primary-50 dark:bg-primary-900/30">
                                        <a
                                            :href="guide.url"
                                            target="_blank"
                                            rel="noopener"
                                            @click="trackGuideClick(guide.key)"
                                            class="flex items-center gap-2.5 flex-1 min-w-0 hover:opacity-80 transition-opacity"
                                        >
                                            <div class="w-8 h-8 bg-primary-500 rounded-md flex items-center justify-center text-white text-xs font-bold shrink-0">
                                                {{ guide.badge }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ guide.label }}</div>
                                            </div>
                                        </a>
                                        <button
                                            v-if="guideVotes?.voting_enabled"
                                            @click="voteForGuide(guide.key)"
                                            :disabled="!inList || votingLoading"
                                            :class="[
                                                'shrink-0 px-2.5 py-1 rounded-md text-xs font-medium transition-all',
                                                userVote === guide.key
                                                    ? 'bg-primary-600 text-white'
                                                    : 'bg-white/80 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-slate-600',
                                                (!inList || votingLoading) ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
                                            ]"
                                        >
                                            {{ userVote === guide.key ? 'Voted' : 'Vote' }}
                                        </button>
                                    </div>
                                    <!-- Vote progress bar -->
                                    <div v-if="guideVotes?.voting_enabled && guideVotes.total_votes > 0" class="px-3 py-2 bg-gray-50 dark:bg-slate-700/30">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                                {{ guideVotes.results[guide.key]?.percentage || 0 }}%
                                            </span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                                {{ guideVotes.results[guide.key]?.count || 0 }} {{ (guideVotes.results[guide.key]?.count || 0) === 1 ? 'vote' : 'votes' }}
                                            </span>
                                        </div>
                                        <div class="w-full h-2 bg-gray-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                            <div
                                                :class="[
                                                    'h-full rounded-full transition-all duration-500',
                                                    guideVotes.winner === guide.key ? 'bg-emerald-500' : 'bg-primary-400 dark:bg-primary-500'
                                                ]"
                                                :style="{ width: (guideVotes.results[guide.key]?.percentage || 0) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Voting hint -->
                            <div v-if="guideVotes?.voting_enabled" class="mt-2">
                                <p v-if="!inList && isAuthenticated" class="text-xs text-gray-500 dark:text-gray-400">
                                    Add this game to your list to vote
                                </p>
                                <p v-else-if="!isAuthenticated" class="text-xs text-gray-500 dark:text-gray-400">
                                    <button @click="loginWithGoogle" class="text-primary-600 dark:text-primary-400 hover:underline">Sign in</button> to vote
                                </p>
                                <p v-if="guideVotes.total_votes > 0" class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ guideVotes.total_votes }} {{ guideVotes.total_votes === 1 ? 'vote' : 'votes' }} total
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommendations Section -->
            <div v-if="loadingRecommendations || recommendations.length" class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Players Also Have</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Based on what other trophy hunters have in their lists
                </p>

                <!-- Loading -->
                <div v-if="loadingRecommendations" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div v-for="n in 6" :key="n" class="animate-pulse">
                        <div class="aspect-[3/4] bg-gray-200 dark:bg-slate-700 rounded-lg mb-2"></div>
                        <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-3/4"></div>
                    </div>
                </div>

                <!-- Recommendations Grid -->
                <div v-else-if="recommendations.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <a
                        v-for="rec in recommendations"
                        :key="rec.game_id"
                        :href="`/game/${rec.slug}`"
                        class="group"
                    >
                        <div class="relative aspect-[3/4] bg-gray-200 dark:bg-slate-700 rounded-lg overflow-hidden mb-2">
                            <img
                                v-if="rec.cover_url"
                                :src="rec.cover_url"
                                :alt="rec.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            />
                            <!-- Overlap Percentage Badge -->
                            <div class="absolute top-2 right-2 px-2 py-1 bg-black/70 rounded text-white text-xs font-bold">
                                {{ rec.percentage }}%
                            </div>
                            <!-- Guide Indicator -->
                            <div v-if="rec.has_guide" class="absolute bottom-2 left-2">
                                <span class="px-1.5 py-0.5 bg-green-500 rounded text-white text-[10px] font-bold">
                                    GUIDE
                                </span>
                            </div>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                            {{ rec.title }}
                        </h3>
                        <div class="flex gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <span v-if="rec.difficulty">{{ rec.difficulty }}/10</span>
                            <span v-if="rec.time_min">{{ rec.time_min }}{{ rec.time_max && rec.time_max !== rec.time_min ? `-${rec.time_max}` : '' }} hrs</span>
                        </div>
                    </a>
                </div>

                <!-- No Recommendations -->
                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>Not enough data yet to show recommendations.</p>
                    <button
                        v-if="!inList"
                        @click="toggleList"
                        :disabled="listLoading"
                        class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors disabled:opacity-50"
                    >
                        <svg v-if="listLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        {{ isAuthenticated ? 'Add to My List' : 'Sign in to add' }}
                    </button>
                    <p v-else class="text-sm mt-1">Thanks for adding! Recommendations improve as more players add games.</p>
                </div>
            </div>

            </div>
        </div>
    <!-- Description Modal -->
    <div
        v-if="showDescriptionModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="showDescriptionModal = false"
    >
        <div class="fixed inset-0 bg-black/60" @click="showDescriptionModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto p-6 z-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ game.title }}</h2>
                <button @click="showDescriptionModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ game.description }}</p>
        </div>
    </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useUserGames } from '../composables/useUserGames'
import { apiPost } from '../utils/api'
import PlatformIcon from '../components/PlatformIcon.vue'
import TrophyIcon from '../components/TrophyIcon.vue'

const props = defineProps({ slug: String })

const { isAuthenticated, loginWithGoogle } = useAuth()
const { addToList, removeFromList, checkInList, updatePreferredGuide, loadUserGameIds } = useUserGames()

const game = ref(null)
const loading = ref(true)
const error = ref(null)
const showDescriptionModal = ref(false)
const recommendations = ref([])
const loadingRecommendations = ref(false)
const inList = ref(false)
const listLoading = ref(false)

// Guide voting
const guideVotes = ref(null)
const userVote = ref(null)
const votingLoading = ref(false)

const guideLabels = {
    psnprofiles: 'PSNProfiles',
    playstationtrophies: 'PlayStationTrophies',
    powerpyx: 'PowerPyx',
}

const guideDefs = [
    { key: 'psnprofiles', label: 'PSNProfiles', badge: 'P', urlField: 'psnprofiles_url' },
    { key: 'playstationtrophies', label: 'PlayStationTrophies', badge: 'PST', urlField: 'playstationtrophies_url' },
    { key: 'powerpyx', label: 'PowerPyx', badge: 'PPX', urlField: 'powerpyx_url' },
]

const availableGuides = computed(() => {
    if (!game.value) return []
    return guideDefs
        .filter(g => game.value[g.urlField])
        .map(g => ({ ...g, url: game.value[g.urlField] }))
})

const hasGuides = computed(() => availableGuides.value.length > 0)

const sortedGuides = computed(() => {
    if (!guideVotes.value?.total_votes) return availableGuides.value
    return [...availableGuides.value].sort((a, b) => {
        const pctA = guideVotes.value.results[a.key]?.percentage || 0
        const pctB = guideVotes.value.results[b.key]?.percentage || 0
        return pctB - pctA
    })
})

// Score display
import { MIN_USER_RATINGS, MIN_CRITIC_SOURCES } from '../constants'

const displayUserScore = computed(() => {
    if (!game.value?.user_score) return null
    const count = game.value.user_score_count
    if (count != null && count < MIN_USER_RATINGS) return 'N/A'
    return game.value.user_score
})

const displayCriticScore = computed(() => {
    if (!game.value?.critic_score) return null
    const count = game.value.critic_score_count
    if (count != null && count < MIN_CRITIC_SOURCES) return 'N/A'
    return game.value.critic_score
})

const userScoreClass = computed(() => {
    const s = displayUserScore.value
    if (s === 'N/A') return 'bg-gray-400 dark:bg-gray-600'
    if (s >= 75) return 'bg-emerald-500'
    if (s >= 50) return 'bg-yellow-500'
    return 'bg-red-500'
})

const criticScoreClass = computed(() => {
    const s = displayCriticScore.value
    if (s === 'N/A') return 'border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500'
    if (s >= 75) return 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
    if (s >= 50) return 'border-yellow-500 text-yellow-600 dark:text-yellow-400'
    return 'border-red-500 text-red-600 dark:text-red-400'
})

// Score tooltip state (mobile tap)
const showScoreTooltip = ref(null)
let scoreTooltipTimer = null

function toggleScoreTooltip(type) {
    if (showScoreTooltip.value === type) {
        showScoreTooltip.value = null
    } else {
        showScoreTooltip.value = type
        clearTimeout(scoreTooltipTimer)
        scoreTooltipTimer = setTimeout(() => { showScoreTooltip.value = null }, 2000)
    }
}

function trackGuideClick(source) {
    if (game.value) {
        apiPost('/guide-clicks', { game_id: game.value.id, guide_source: source })
    }
}

function buildDescription() {
    if (!game.value) return ''

    const parts = [`Trophy guide for ${game.value.title}.`]

    if (game.value.difficulty) {
        parts.push(`Difficulty: ${game.value.difficulty}/10.`)
    }
    if (game.value.time_min) {
        const time = game.value.time_max && game.value.time_max !== game.value.time_min
            ? `${game.value.time_min}-${game.value.time_max}`
            : game.value.time_min
        parts.push(`Time: ${time} hours.`)
    }
    if (game.value.playthroughs_required) {
        parts.push(`${game.value.playthroughs_required} playthrough${game.value.playthroughs_required > 1 ? 's' : ''} required.`)
    }

    const guideCount = [game.value.psnprofiles_url, game.value.playstationtrophies_url, game.value.powerpyx_url].filter(Boolean).length
    if (guideCount > 0) {
        parts.push(`${guideCount} guide${guideCount > 1 ? 's' : ''} available.`)
    }

    return parts.join(' ')
}

async function fetchGame() {
    loading.value = true
    error.value = null

    try {
        const response = await fetch(`/api/games/${props.slug}`)
        if (!response.ok) {
            throw new Error('Game not found')
        }
        game.value = await response.json()
        // Fetch secondary data after a short delay to avoid flooding 1 vCPU server
        fetchGuideVotes()
        checkListStatus()
        // Recommendations are heavy (JOIN + GROUP BY) — load after main content renders
        setTimeout(fetchRecommendations, 100)
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

async function checkListStatus() {
    if (!isAuthenticated.value || !game.value) return

    // Ensure user game IDs are loaded before checking (may still be loading from app.js)
    await loadUserGameIds()
    const result = checkInList(game.value.id)
    inList.value = result.in_list
    userVote.value = result.preferred_guide || null
}

async function fetchGuideVotes() {
    if (!game.value) return

    try {
        const response = await fetch(`/api/games/${game.value.slug}/guide-votes`)
        if (response.ok) {
            guideVotes.value = await response.json()
        }
    } catch (e) {
        console.error('Failed to fetch guide votes:', e)
    }
}

async function voteForGuide(guide) {
    if (!isAuthenticated.value) {
        loginWithGoogle()
        return
    }

    if (!inList.value || votingLoading.value) return

    votingLoading.value = true
    try {
        await updatePreferredGuide(game.value.id, guide)
        userVote.value = guide
        // Refresh vote counts
        await fetchGuideVotes()
    } catch (e) {
        console.error('Failed to vote:', e)
    } finally {
        votingLoading.value = false
    }
}

async function toggleList() {
    if (!isAuthenticated.value) {
        loginWithGoogle()
        return
    }

    if (listLoading.value || !game.value) return

    listLoading.value = true
    try {
        if (inList.value) {
            await removeFromList(game.value.id)
            inList.value = false
        } else {
            await addToList(game.value.id)
            inList.value = true
        }
    } catch (e) {
        console.error('Failed to update list:', e)
    } finally {
        listLoading.value = false
    }
}

async function fetchRecommendations() {
    if (!game.value) return

    loadingRecommendations.value = true
    try {
        const response = await fetch(`/api/games/${game.value.slug}/recommendations`)
        if (response.ok) {
            const data = await response.json()
            recommendations.value = data.recommendations || []
        }
    } catch (e) {
        console.error('Failed to fetch recommendations:', e)
        recommendations.value = []
    } finally {
        loadingRecommendations.value = false
    }
}

onMounted(fetchGame)

// Check list status when auth state changes
watch(isAuthenticated, (newVal) => {
    if (newVal && game.value) {
        checkListStatus()
    } else {
        inList.value = false
    }
})
</script>

<style scoped>
.platform-icon-white :deep(img) {
    filter: brightness(0) invert(1) !important;
}
</style>
