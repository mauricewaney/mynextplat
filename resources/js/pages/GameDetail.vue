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
            <!-- Hero Banner -->
            <div
                v-if="game.banner_url"
                class="relative h-64 md:h-80 lg:h-96 bg-cover bg-center"
                :style="{ backgroundImage: `url(${game.banner_url})` }"
            >
                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/60 to-transparent"></div>

                <!-- Content -->
                <div class="absolute inset-0 flex items-end">
                    <div class="max-w-6xl mx-auto px-4 pb-6 w-full">
                        <!-- Breadcrumb -->
                        <nav class="mb-3 text-sm">
                            <a href="/" class="text-gray-300 hover:text-white">Home</a>
                            <span class="mx-2 text-gray-500">/</span>
                            <span class="text-gray-400">{{ game.title }}</span>
                        </nav>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white drop-shadow-lg">
                            {{ game.title }}
                        </h1>
                    </div>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4 py-8">
                <!-- Breadcrumb (only if no banner) -->
                <nav v-if="!game.banner_url" class="mb-6 text-sm">
                    <a href="/" class="text-primary-600 hover:underline">Home</a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ game.title }}</span>
                </nav>

            <!-- Header Section -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg mb-8">
                <!-- Top: Cover + Title/Platforms/Developer side-by-side -->
                <div class="flex p-4 sm:p-6 gap-4 sm:gap-6">
                    <!-- Cover Image -->
                    <div class="w-28 sm:w-48 md:w-56 shrink-0">
                        <img
                            v-if="game.cover_url"
                            :src="game.cover_url"
                            :alt="game.title + ' cover'"
                            class="w-full h-auto rounded-lg"
                        />
                        <div v-else class="w-full aspect-[3/4] bg-gray-200 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                            <span class="text-gray-400 text-4xl">?</span>
                        </div>
                        <div v-if="game.has_platinum || game.gold_count || game.silver_count || game.bronze_count" class="flex items-center justify-center gap-2 sm:gap-3 mt-2">
                            <span v-if="game.has_platinum" class="inline-flex items-center gap-0.5 sm:gap-1 text-xs sm:text-sm font-bold text-blue-300 dark:text-blue-200">
                                <TrophyIcon tier="platinum" size="xs" class="sm:!w-4 sm:!h-4" />1
                            </span>
                            <span v-if="game.gold_count" class="inline-flex items-center gap-0.5 sm:gap-1 text-xs sm:text-sm font-bold text-yellow-500 dark:text-yellow-400">
                                <TrophyIcon tier="gold" size="xs" class="sm:!w-4 sm:!h-4" />{{ game.gold_count }}
                            </span>
                            <span v-if="game.silver_count" class="inline-flex items-center gap-0.5 sm:gap-1 text-xs sm:text-sm font-bold text-gray-400 dark:text-gray-300">
                                <TrophyIcon tier="silver" size="xs" class="sm:!w-4 sm:!h-4" />{{ game.silver_count }}
                            </span>
                            <span v-if="game.bronze_count" class="inline-flex items-center gap-0.5 sm:gap-1 text-xs sm:text-sm font-bold text-amber-700 dark:text-amber-500">
                                <TrophyIcon tier="bronze" size="xs" class="sm:!w-4 sm:!h-4" />{{ game.bronze_count }}
                            </span>
                        </div>
                    </div>

                    <!-- Info beside cover -->
                    <div class="flex-1 min-w-0 flex flex-col">
                        <h1 v-if="!game.banner_url" class="text-lg sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ game.title }}
                        </h1>

                        <!-- Platforms + Developer / Scores row -->
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div class="min-w-0">
                                <!-- Platforms -->
                                <div v-if="game.platforms?.length" class="flex flex-wrap gap-1.5 sm:gap-2 mb-1.5">
                                    <span
                                        v-for="platform in game.platforms"
                                        :key="platform.id"
                                        class="h-7 sm:h-10 px-1.5 sm:px-2 inline-flex items-center bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 text-xs sm:text-sm font-medium rounded"
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
                        <p v-if="game.description" class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-6 sm:line-clamp-none">
                            {{ game.description }}
                        </p>
                    </div>
                </div>

                <!-- Bottom: Stats, Genres, Actions (full width) -->
                <div class="px-4 sm:px-6 pb-4 sm:pb-6">
                    <!-- Trophy Stats Grid -->
                    <div v-if="hasGuides" class="grid grid-cols-4 gap-2 sm:gap-4 mb-4 sm:mb-6" :class="game.data_source ? 'sm:grid-cols-5' : ''">
                        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-2 sm:p-3 text-center">
                            <div class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                                {{ game.difficulty || '?' }}<span class="text-xs sm:text-sm font-normal">/10</span>
                            </div>
                            <div class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">Difficulty</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-2 sm:p-3 text-center">
                            <div class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                                {{ game.time_min || '?' }}<span v-if="game.time_max && game.time_max !== game.time_min" class="text-sm">-{{ game.time_max }}</span>
                            </div>
                            <div class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">Hours</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-2 sm:p-3 text-center">
                            <div class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                                {{ game.playthroughs_required ? game.playthroughs_required + '+' : '?' }}
                            </div>
                            <div class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">Playthroughs</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-2 sm:p-3 text-center">
                            <div class="text-lg sm:text-2xl font-bold" :class="game.missable_trophies ? 'text-red-500' : 'text-green-500'">
                                {{ game.missable_trophies ? 'Yes' : 'No' }}
                            </div>
                            <div class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">Missables</div>
                        </div>
                        <div v-if="game.data_source" class="bg-gray-50 dark:bg-slate-700 rounded-lg p-2 sm:p-3 text-center col-span-4 sm:col-span-1">
                            <div
                                class="text-base sm:text-lg font-bold"
                                :class="
                                    game.data_source === 'playstationtrophies' ? 'text-teal-600 dark:text-teal-400' :
                                    game.data_source === 'powerpyx' ? 'text-amber-600 dark:text-amber-400' :
                                    game.data_source === 'psnprofiles' ? 'text-sky-600 dark:text-sky-400' :
                                    'text-gray-600 dark:text-gray-400'
                                "
                            >
                                {{ game.data_source === 'playstationtrophies' ? 'PlayStationTrophies' : game.data_source === 'powerpyx' ? 'PowerPyx' : game.data_source === 'psnprofiles' ? 'PSNProfiles' : game.data_source }}
                            </div>
                            <div class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">Source</div>
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

                    <!-- Online Trophies Status -->
                    <div v-if="game.has_online_trophies" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-4">
                        <span class="text-red-700 dark:text-red-300 text-sm font-medium">
                            This game has online trophies
                        </span>
                    </div>
                    <div v-else-if="game.has_online_trophies === false" class="bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800 rounded-lg p-3 mb-4">
                        <span class="text-primary-700 dark:text-primary-300 text-sm font-medium">
                            No online trophies required
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
                        <!-- Add to List Button -->
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
                            <!-- Loading -->
                            <svg v-if="listLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <!-- Check icon (in list) -->
                            <svg v-else-if="inList" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <!-- Plus icon (not in list) -->
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span v-if="!isAuthenticated">Sign in to add</span>
                            <span v-else-if="inList">In My List</span>
                            <span v-else>Add to My List</span>
                        </button>

                        <!-- Report Issue Button -->
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
            </div>

            <!-- Trophy Guides Section -->
            <div v-if="hasGuides" class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Trophy Guides</h2>

                <div class="grid md:grid-cols-3 gap-4">
                    <!-- PSNProfiles -->
                    <a
                        v-if="game.psnprofiles_url"
                        :href="game.psnprofiles_url"
                        target="_blank"
                        rel="noopener"
                        @click="trackGuideClick('psnprofiles')"
                        class="flex items-center gap-3 p-4 bg-primary-50 dark:bg-primary-900/30 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/50 transition-colors"
                    >
                        <div class="w-10 h-10 bg-primary-500 rounded-lg flex items-center justify-center text-white font-bold">
                            P
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">PSNProfiles</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">View Guide</div>
                        </div>
                    </a>

                    <!-- PlayStationTrophies -->
                    <a
                        v-if="game.playstationtrophies_url"
                        :href="game.playstationtrophies_url"
                        target="_blank"
                        rel="noopener"
                        @click="trackGuideClick('playstationtrophies')"
                        class="flex items-center gap-3 p-4 bg-primary-50 dark:bg-primary-900/30 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/50 transition-colors"
                    >
                        <div class="w-10 h-10 bg-primary-500 rounded-lg flex items-center justify-center text-white font-bold">
                            PST
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">PlayStationTrophies</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">View Guide</div>
                        </div>
                    </a>

                    <!-- PowerPyx -->
                    <a
                        v-if="game.powerpyx_url"
                        :href="game.powerpyx_url"
                        target="_blank"
                        rel="noopener"
                        @click="trackGuideClick('powerpyx')"
                        class="flex items-center gap-3 p-4 bg-primary-50 dark:bg-primary-900/30 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/50 transition-colors"
                    >
                        <div class="w-10 h-10 bg-primary-500 rounded-lg flex items-center justify-center text-white font-bold">
                            PPX
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">PowerPyx</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">View Guide</div>
                        </div>
                    </a>
                </div>

                <!-- Guide Voting (only when 2+ guides available) -->
                <div v-if="guideVotes?.voting_enabled" class="mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <!-- Vote Result Banner (when enough votes) -->
                    <div v-if="guideVotes.winner && guideVotes.total_votes >= 3" class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-sm text-green-700 dark:text-green-400 font-medium">
                            {{ guideVotes.winner_percentage }}% of users found the {{ guideLabels[guideVotes.winner] }} guide most helpful
                        </p>
                    </div>

                    <!-- Voting UI -->
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Which guide did you find most helpful?</span>
                        <div class="flex flex-wrap gap-3">
                            <label
                                v-for="guide in guideVotes.available_guides"
                                :key="guide"
                                :class="[
                                    'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-colors text-sm',
                                    userVote === guide
                                        ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500'
                                        : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600',
                                    (!inList || votingLoading) ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                            >
                                <input
                                    type="radio"
                                    name="preferred_guide"
                                    :value="guide"
                                    :checked="userVote === guide"
                                    :disabled="!inList || votingLoading"
                                    @change="voteForGuide(guide)"
                                    class="sr-only"
                                />
                                <span :class="[
                                    'w-4 h-4 rounded-full border-2 flex items-center justify-center',
                                    userVote === guide
                                        ? 'border-primary-500 bg-primary-500'
                                        : 'border-gray-400 dark:border-gray-500'
                                ]">
                                    <span v-if="userVote === guide" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                                {{ guideLabels[guide] }}
                                <span v-if="guideVotes.total_votes > 0" class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ guideVotes.results[guide]?.percentage || 0 }}%)
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Not in list hint -->
                    <p v-if="!inList && isAuthenticated" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Add this game to your list to vote
                    </p>
                    <p v-else-if="!isAuthenticated" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <button @click="loginWithGoogle" class="text-primary-600 dark:text-primary-400 hover:underline">Sign in</button> to vote
                    </p>
                </div>
            </div>

            <!-- Recommendations Section -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 mb-8">
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
const { addToList, removeFromList, checkInList, updatePreferredGuide } = useUserGames()

const game = ref(null)
const loading = ref(true)
const error = ref(null)
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

const hasGuides = computed(() => {
    return game.value?.psnprofiles_url || game.value?.playstationtrophies_url || game.value?.powerpyx_url
})

// Score display
const MIN_USER_RATINGS = 3
const MIN_CRITIC_SOURCES = 3

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

function checkListStatus() {
    if (!isAuthenticated.value || !game.value) return

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
