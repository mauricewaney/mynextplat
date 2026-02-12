<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

            <!-- Modal -->
            <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">
                            {{ isEdit ? 'Edit Game' : 'Add New Game' }}
                        </h2>
                        <!-- Guide Links -->
                        <div v-if="isEdit && (game?.psnprofiles_url || game?.playstationtrophies_url || game?.powerpyx_url)" class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-500">Open guide:</span>
                            <a
                                v-if="game?.psnprofiles_url"
                                :href="game.psnprofiles_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium hover:bg-blue-200"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                PSNP
                            </a>
                            <a
                                v-if="game?.playstationtrophies_url"
                                :href="game.playstationtrophies_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-medium hover:bg-purple-200"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                PST
                            </a>
                            <a
                                v-if="game?.powerpyx_url"
                                :href="game.powerpyx_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-medium hover:bg-orange-200"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                PPX
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="isEdit"
                            type="button"
                            @click="submitForm"
                            class="px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium disabled:opacity-50"
                            :disabled="loading"
                        >
                            {{ loading ? 'Saving...' : 'Update' }}
                        </button>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <!-- Trophy Data (priority fields) -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Trophy Data</h3>

                        <!-- Data Source Badge -->
                        <div v-if="form.data_source" class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Data source:</span>
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-bold',
                                    form.data_source === 'playstationtrophies' ? 'bg-purple-100 text-purple-700' :
                                    form.data_source === 'powerpyx' ? 'bg-orange-100 text-orange-700' :
                                    form.data_source === 'psnprofiles' ? 'bg-blue-100 text-blue-700' :
                                    'bg-gray-100 text-gray-700'
                                ]"
                            >
                                {{ form.data_source === 'playstationtrophies' ? 'PST' : form.data_source === 'powerpyx' ? 'PPX' : form.data_source === 'psnprofiles' ? 'PSNP' : form.data_source }}
                            </span>
                        </div>

                        <!-- Difficulty -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Difficulty (1-10)
                            </label>
                            <input
                                v-model.number="form.difficulty"
                                type="number"
                                min="1"
                                max="10"
                                step="1"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="5"
                            />
                            <p class="text-xs text-gray-500 mt-1">1 = Very Easy, 10 = Extremely Hard</p>
                        </div>

                        <!-- Time Range -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Min Time (hours)</label>
                                <input
                                    v-model.number="form.time_min"
                                    type="number"
                                    min="0"
                                    step="0.5"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="50"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max Time (hours)</label>
                                <input
                                    v-model.number="form.time_max"
                                    type="number"
                                    min="0"
                                    step="0.5"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="60"
                                />
                            </div>
                        </div>

                        <!-- Playthroughs Required -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Playthroughs Required</label>
                            <input
                                v-model.number="form.playthroughs_required"
                                type="number"
                                min="1"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="1"
                            />
                        </div>

                        <!-- Trophy Booleans -->
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    v-model="form.has_online_trophies"
                                    type="checkbox"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                />
                                <span class="text-sm font-medium text-gray-700">Has Online Trophies</span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    v-model="form.missable_trophies"
                                    type="checkbox"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                />
                                <span class="text-sm font-medium text-gray-700">Has Missable Trophies</span>
                            </label>
                        </div>

                        <!-- Verified -->
                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input
                                    v-model="form.is_verified"
                                    type="checkbox"
                                    class="w-5 h-5 text-green-600 rounded focus:ring-2 focus:ring-green-500"
                                />
                                <div>
                                    <span class="text-sm font-semibold text-green-700">Mark as Verified</span>
                                    <p class="text-xs text-green-600">Data has been manually checked and confirmed accurate</p>
                                </div>
                            </label>
                        </div>

                        <!-- Quick Fill from Guide -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Quick Fill (paste guide info)
                                <span class="text-xs text-gray-400 ml-2">Paste to auto-save, or Enter to save manually</span>
                            </label>
                            <textarea
                                v-model="guideText"
                                @paste="onGuidePaste"
                                @input="parseGuideText"
                                @keydown.enter.prevent="onQuickFillEnter"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-mono"
                                placeholder="Paste guide info here, e.g.:
Difficulty: 7/10
Time: 40-50 hours
Missable Trophies: 12
Online Trophies: None"
                            ></textarea>
                            <p v-if="parsedFields.length > 0" class="text-xs text-green-600 mt-1">
                                Parsed: {{ parsedFields.join(', ') }}
                            </p>
                            <p v-else class="text-xs text-gray-500 mt-1">
                                Paste text from PSNProfiles, PlayStationTrophies, or PowerPyx to auto-fill fields
                            </p>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Basic Information</h3>

                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Game Title <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., Ghost of Tsushima"
                            />
                            <p v-if="errors.title" class="text-red-500 text-sm mt-1">{{ errors.title[0] }}</p>
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Slug (URL-friendly, auto-generated if empty)
                            </label>
                            <input
                                v-model="form.slug"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="ghost-of-tsushima"
                            />
                            <p v-if="errors.slug" class="text-red-500 text-sm mt-1">{{ errors.slug[0] }}</p>
                        </div>

                        <!-- Developer & Publisher -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Developer</label>
                                <input
                                    v-model="form.developer"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Sucker Punch Productions"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                                <input
                                    v-model="form.publisher"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Sony Interactive Entertainment"
                                />
                            </div>
                        </div>

                        <!-- Release Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Release Date</label>
                            <input
                                v-model="form.release_date"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Server & Obtainability -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Server & Obtainability</h3>

                        <!-- Server Shutdown Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Server Shutdown Date</label>
                            <input
                                v-model="form.server_shutdown_date"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        <!-- Unobtainable Warning -->
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input
                                    v-model="form.is_unobtainable"
                                    type="checkbox"
                                    class="w-5 h-5 text-red-600 rounded focus:ring-2 focus:ring-red-500"
                                />
                                <div>
                                    <span class="text-sm font-semibold text-red-700">Platinum is Unobtainable</span>
                                    <p class="text-xs text-red-600">Server shutdown, delisted, or otherwise impossible to 100%</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Scores & Reviews -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Scores & Reviews</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Critic Score (0-100)</label>
                                <input
                                    v-model.number="form.critic_score"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="85"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">OpenCritic Score (0-100)</label>
                                <input
                                    v-model.number="form.opencritic_score"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="88"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Pricing (Optional)</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Price (€)</label>
                                <input
                                    v-model.number="form.price_current"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="69.99"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Original Price (€)</label>
                                <input
                                    v-model.number="form.price_original"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="79.99"
                                />
                            </div>
                        </div>

                        <!-- Affiliate Links -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amazon Link</label>
                                <input
                                    v-model="form.amazon_link"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://amazon.com/..."
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bol.com Link</label>
                                <input
                                    v-model="form.bol_link"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://bol.com/..."
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Categories</h3>

                        <!-- Genres -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Genres</label>
                            <div class="border border-gray-300 rounded-md p-3 max-h-48 overflow-y-auto">
                                <label
                                    v-for="genre in genres"
                                    :key="genre.id"
                                    class="flex items-center space-x-2 py-1 hover:bg-gray-50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="genre.id"
                                        v-model="form.genre_ids"
                                        class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    />
                                    <span class="text-sm">{{ genre.name }}</span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Selected: {{ form.genre_ids.length }}</p>
                        </div>

                        <!-- Tags -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                            <div class="border border-gray-300 rounded-md p-3 max-h-48 overflow-y-auto">
                                <label
                                    v-for="tag in tags"
                                    :key="tag.id"
                                    class="flex items-center space-x-2 py-1 hover:bg-gray-50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="tag.id"
                                        v-model="form.tag_ids"
                                        class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    />
                                    <span class="text-sm">{{ tag.name }}</span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Selected: {{ form.tag_ids.length }}</p>
                        </div>

                        <!-- Platforms -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Platforms <span class="text-red-500">*</span>
                            </label>
                            <div class="border border-gray-300 rounded-md p-3">
                                <label
                                    v-for="platform in platforms"
                                    :key="platform.id"
                                    class="flex items-center space-x-2 py-1 hover:bg-gray-50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="platform.id"
                                        v-model="form.platform_ids"
                                        class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    />
                                    <span class="text-sm font-medium">{{ platform.name }}</span>
                                </label>
                            </div>
                            <p v-if="errors.platform_ids" class="text-red-500 text-sm mt-1">{{ errors.platform_ids[0] }}</p>
                            <p v-else class="text-xs text-gray-500 mt-1">At least one platform required. Selected: {{ form.platform_ids.length }}</p>
                        </div>
                    </div>

                    <!-- Images & Links -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Images & External Links</h3>

                        <!-- Image URLs -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cover Image URL</label>
                                <input
                                    v-model="form.cover_url"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://..."
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Banner Image URL</label>
                                <input
                                    v-model="form.banner_url"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://..."
                                />
                            </div>
                        </div>

                        <!-- Guide Search -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Search Existing Guides
                                <span class="text-xs text-gray-400 ml-1">(find guides from other versions/regions)</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="guideSearch"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Search game title to find existing guides..."
                                    @input="searchGuides"
                                />
                                <svg v-if="guideSearchLoading" class="absolute right-3 top-2.5 w-5 h-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                            <!-- Search Results -->
                            <div v-if="guideSearchResults.length > 0" class="mt-2 border border-gray-200 rounded-md max-h-60 overflow-y-auto">
                                <div
                                    v-for="result in guideSearchResults"
                                    :key="result.id"
                                    class="p-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-sm text-gray-900 truncate">{{ result.title }}</p>
                                            <p class="text-xs text-gray-500">{{ result.platforms?.map(p => p.short_name).join(', ') }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <button
                                            v-if="result.psnprofiles_url"
                                            @click="applyGuide('psnprofiles_url', result.psnprofiles_url)"
                                            type="button"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium hover:bg-blue-200"
                                            title="Use this PSNProfiles guide"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            PSNP
                                        </button>
                                        <button
                                            v-if="result.playstationtrophies_url"
                                            @click="applyGuide('playstationtrophies_url', result.playstationtrophies_url)"
                                            type="button"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-medium hover:bg-purple-200"
                                            title="Use this PS Trophies guide"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            PST
                                        </button>
                                        <button
                                            v-if="result.powerpyx_url"
                                            @click="applyGuide('powerpyx_url', result.powerpyx_url)"
                                            type="button"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-medium hover:bg-orange-200"
                                            title="Use this PowerPyx guide"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            PPX
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p v-else-if="guideSearch && !guideSearchLoading && guideSearchResults.length === 0" class="text-xs text-gray-500 mt-1">
                                No games with guides found matching "{{ guideSearch }}"
                            </p>
                        </div>

                        <!-- Guide URLs -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PSNProfiles URL</label>
                                <input
                                    v-model="form.psnprofiles_url"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://psnprofiles.com/..."
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PlayStationTrophies URL</label>
                                <input
                                    v-model="form.playstationtrophies_url"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://www.playstationtrophies.org/..."
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PowerPyx URL</label>
                                <input
                                    v-model="form.powerpyx_url"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://www.powerpyx.com/..."
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PSN Store URL</label>
                                <input
                                    v-model="form.psn_store_url"
                                    type="url"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="https://store.playstation.com/..."
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Game description..."
                        ></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                            :disabled="loading"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="loading"
                        >
                            <span v-if="loading">Saving...</span>
                            <span v-else>{{ isEdit ? 'Update Game' : 'Create Game' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    game: {
        type: Object,
        default: null
    },
    genres: {
        type: Array,
        required: true
    },
    tags: {
        type: Array,
        required: true
    },
    platforms: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const errors = ref({});
const guideText = ref('');
const parsedFields = ref([]);

// Guide search
const guideSearch = ref('');
const guideSearchLoading = ref(false);
const guideSearchResults = ref([]);
let guideSearchTimeout = null;

// Form data
const form = ref({
    title: '',
    slug: '',
    description: '',
    developer: '',
    publisher: '',
    release_date: '',
    difficulty: null,
    time_min: null,
    time_max: null,
    playthroughs_required: 1,
    has_online_trophies: false,
    missable_trophies: false,
    is_unobtainable: false,
    server_shutdown_date: '',
    is_verified: false,
    data_source: '',
    critic_score: null,
    opencritic_score: null,
    price_current: null,
    price_original: null,
    amazon_link: '',
    bol_link: '',
    cover_url: '',
    banner_url: '',
    psnprofiles_url: '',
    playstationtrophies_url: '',
    powerpyx_url: '',
    psn_store_url: '',
    genre_ids: [],
    tag_ids: [],
    platform_ids: []
});

const isEdit = computed(() => !!props.game);

// Format date for HTML date input (YYYY-MM-DD)
function formatDateForInput(dateString) {
    if (!dateString) return ''
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return ''
    return date.toISOString().split('T')[0]
}

// Watch for game prop changes (when editing)
watch(() => props.game, (game) => {
    if (game) {
        form.value = {
            title: game.title || '',
            slug: game.slug || '',
            description: game.description || '',
            developer: game.developer || '',
            publisher: game.publisher || '',
            release_date: formatDateForInput(game.release_date),
            difficulty: game.difficulty || null,
            time_min: game.time_min || null,
            time_max: game.time_max || null,
            playthroughs_required: game.playthroughs_required || 1,
            has_online_trophies: game.has_online_trophies || false,
            missable_trophies: game.missable_trophies || false,
            is_unobtainable: game.is_unobtainable || false,
            server_shutdown_date: formatDateForInput(game.server_shutdown_date),
            is_verified: game.is_verified || false,
            data_source: game.data_source || '',
            critic_score: game.critic_score || null,
            opencritic_score: game.opencritic_score || null,
            price_current: game.price_current || null,
            price_original: game.price_original || null,
            amazon_link: game.amazon_link || '',
            bol_link: game.bol_link || '',
            cover_url: game.cover_url || '',
            banner_url: game.banner_url || '',
            psnprofiles_url: game.psnprofiles_url || '',
            playstationtrophies_url: game.playstationtrophies_url || '',
            powerpyx_url: game.powerpyx_url || '',
            psn_store_url: game.psn_store_url || '',
            genre_ids: game.genres?.map(g => g.id) || [],
            tag_ids: game.tags?.map(t => t.id) || [],
            platform_ids: game.platforms?.map(p => p.id) || []
        };
        // Clear guide search when loading a game
        guideSearch.value = '';
        guideSearchResults.value = [];
    }
}, { immediate: true });

// Reset form when modal closes
watch(() => props.show, (show) => {
    if (!show && !props.game) {
        resetForm();
    }
    errors.value = {};
    guideText.value = '';
    parsedFields.value = [];
});

function resetForm() {
    form.value = {
        title: '',
        slug: '',
        description: '',
        developer: '',
        publisher: '',
        release_date: '',
        difficulty: null,
        time_min: null,
        time_max: null,
        playthroughs_required: 1,
        has_online_trophies: false,
        missable_trophies: false,
        is_unobtainable: false,
        server_shutdown_date: '',
        is_verified: false,
        data_source: '',
        critic_score: null,
        opencritic_score: null,
        price_current: null,
        price_original: null,
        amazon_link: '',
        bol_link: '',
        cover_url: '',
        banner_url: '',
        psnprofiles_url: '',
        playstationtrophies_url: '',
        powerpyx_url: '',
        psn_store_url: '',
        genre_ids: [],
        tag_ids: [],
        platform_ids: []
    };
    guideSearch.value = '';
    guideSearchResults.value = [];
}

// Auto-detect data_source when guide URLs change (only if currently empty)
watch(
    () => [form.value.playstationtrophies_url, form.value.powerpyx_url, form.value.psnprofiles_url],
    () => {
        if (form.value.data_source) return
        if (form.value.playstationtrophies_url) {
            form.value.data_source = 'playstationtrophies'
        } else if (form.value.powerpyx_url) {
            form.value.data_source = 'powerpyx'
        } else if (form.value.psnprofiles_url) {
            form.value.data_source = 'psnprofiles'
        }
    }
)

// Guide search functions
function searchGuides() {
    clearTimeout(guideSearchTimeout);

    if (!guideSearch.value || guideSearch.value.length < 2) {
        guideSearchResults.value = [];
        return;
    }

    guideSearchTimeout = setTimeout(async () => {
        guideSearchLoading.value = true;
        try {
            const response = await axios.get('/api/admin/games/search-guides', {
                params: { search: guideSearch.value }
            });
            guideSearchResults.value = response.data;
        } catch (error) {
            console.error('Error searching guides:', error);
            guideSearchResults.value = [];
        } finally {
            guideSearchLoading.value = false;
        }
    }, 300);
}

function applyGuide(field, url) {
    form.value[field] = url;
    // Visual feedback
    const el = document.querySelector(`input[placeholder*="${field.includes('psnprofiles') ? 'psnprofiles' : field.includes('playstationtrophies') ? 'playstationtrophies' : 'powerpyx'}"]`);
    if (el) {
        el.classList.add('ring-2', 'ring-green-500');
        setTimeout(() => el.classList.remove('ring-2', 'ring-green-500'), 1000);
    }
}

async function submitForm() {
    loading.value = true;
    errors.value = {};

    try {
        if (isEdit.value) {
            // Update existing game
            const response = await axios.put(`/api/admin/games/${props.game.id}`, form.value);
            emit('saved', response.data);
        } else {
            // Create new game
            const response = await axios.post('/api/admin/games', form.value);
            emit('saved', response.data);
        }

        emit('close');
        resetForm();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            // Validation errors
            errors.value = error.response.data.errors;
        } else {
            alert('An error occurred while saving the game. Please try again.');
            console.error('Error saving game:', error);
        }
    } finally {
        loading.value = false;
    }
}

function closeModal() {
    if (!loading.value) {
        emit('close');
    }
}

// Guide text parsing
function onGuidePaste(event) {
    // Let the paste happen, then parse after a tick
    setTimeout(() => {
        parseGuideText();
        // Auto-save if we parsed at least one field
        if (parsedFields.value.length > 0) {
            setTimeout(() => {
                submitForm();
            }, 300); // Brief delay so user sees what was parsed
        }
    }, 0);
}

function onQuickFillEnter() {
    // Parse first if not already parsed
    parseGuideText();
    // Then submit
    submitForm();
}

function parseGuideText() {
    const text = guideText.value;
    const textLower = text.toLowerCase();
    const parsed = [];

    // Parse difficulty
    // PowerPyx format: "Difficulty: 7/10", "Difficulty Rating: 7"
    // PSNProfiles format: "5/10" on its own line, or "5/10 Difficulty"
    const difficultyMatch = text.match(/difficulty[:\s]*(\d+(?:\.\d+)?)\s*(?:\/\s*10)?/i)
        || text.match(/^(\d+(?:\.\d+)?)\s*\/\s*10\s*$/m)  // PSNProfiles: "5/10" on own line
        || text.match(/(\d+(?:\.\d+)?)\s*\/\s*10\s*difficulty/i)  // "5/10 Difficulty"
        || text.match(/(\d+(?:\.\d+)?)\s*\/\s*10/i);
    if (difficultyMatch) {
        const diff = Math.round(parseFloat(difficultyMatch[1]));
        if (diff >= 1 && diff <= 10) {
            form.value.difficulty = diff;
            parsed.push('difficulty');
        }
    }

    // Parse time
    // PowerPyx format: "Time: 40-50 hours", "Approximate amount of time to platinum: 40-50 hours"
    // PSNProfiles format: "70 Hours" (number before Hours), "70-100 Hours"
    const timeMatch = text.match(/time(?:\s+to\s+platinum)?[:\s]*(\d+)\s*[-–to]+\s*(\d+)\+?\s*(?:hours?|hrs?)?/i)
        || text.match(/(\d+)\s*[-–to]+\s*(\d+)\+?\s*(?:hours?|hrs?)/i)
        || text.match(/^(\d+)\s*(?:hours?|hrs?)\s*$/im)  // PSNProfiles: "70 Hours" on own line
        || text.match(/time(?:\s+to\s+platinum)?[:\s]*(\d+)\+?\s*(?:hours?|hrs?)/i)
        || text.match(/(\d+)\+?\s*(?:hours?|hrs?)/i);
    if (timeMatch) {
        if (timeMatch[2]) {
            // Range: 40-50 hours
            form.value.time_min = parseInt(timeMatch[1]);
            form.value.time_max = parseInt(timeMatch[2]);
        } else {
            // Single value: 50 hours or 50+ hours
            form.value.time_min = parseInt(timeMatch[1]);
            form.value.time_max = parseInt(timeMatch[1]);
        }
        parsed.push('time');
    }

    // Parse playthroughs
    // PSNProfiles format: "1 Playthrough" (number BEFORE word)
    // PowerPyx format: "Playthroughs: 2", "Minimum Playthroughs: 1" (number AFTER word, REQUIRES colon)
    const playthroughMatch = text.match(/(\d+)\s+playthrough[s]?(?!\s*:)/i)  // PSNProfiles: "1 Playthrough" (number before, no colon after)
        || text.match(/playthrough[s]?\s*:\s*(\d+)/i);  // PowerPyx: "Playthroughs: 2" (requires colon)
    if (playthroughMatch) {
        form.value.playthroughs_required = parseInt(playthroughMatch[1]);
        parsed.push('playthroughs');
    }

    // Parse missable trophies
    // PowerPyx format: "Missable Trophies: 12", "Missable: None"
    // PSNProfiles format: Has a "Missable" section header (not "Unmissable") with trophies listed after
    const missableMatch = text.match(/missable\s*(?:trophies?)?[:\s]*(\d+|yes|no|none)/i)
        || text.match(/(\d+)\s*missable/i);
    if (missableMatch) {
        const val = missableMatch[1]?.toLowerCase();
        if (val === 'no' || val === 'none' || val === '0') {
            form.value.missable_trophies = false;
        } else {
            form.value.missable_trophies = true;
        }
        parsed.push('missables');
    } else if (textLower.includes('no missable')) {
        form.value.missable_trophies = false;
        parsed.push('missables');
    } else if (/^missable\s*$/im.test(text)) {
        // PSNProfiles: "Missable" as a section header (on its own line) means there ARE missables
        form.value.missable_trophies = true;
        parsed.push('missables');
    }

    // Parse online trophies
    // PowerPyx format: "Online Trophies: 5", "Online: 0"
    // PSNProfiles format: Has an "Online" section header with trophies listed after
    const onlineMatch = text.match(/online\s*(?:trophies?)?[:\s]*(\d+|yes|no|none)/i)
        || text.match(/(\d+)\s*online\s*troph/i);
    if (onlineMatch) {
        const val = onlineMatch[1]?.toLowerCase();
        if (val === 'no' || val === 'none' || val === '0') {
            form.value.has_online_trophies = false;
        } else {
            form.value.has_online_trophies = true;
        }
        parsed.push('online');
    } else if (textLower.includes('no online')) {
        form.value.has_online_trophies = false;
        parsed.push('online');
    } else if (/^online\s*$/im.test(text)) {
        // PSNProfiles: "Online" as a section header means there ARE online trophies
        form.value.has_online_trophies = true;
        parsed.push('online');
    }

    parsedFields.value = parsed;
}
</script>
