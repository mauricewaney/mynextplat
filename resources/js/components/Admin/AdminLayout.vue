<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-14">
                    <div class="flex items-center">
                        <router-link to="/" class="flex-shrink-0 flex items-center gap-2 group">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <h1 class="text-lg font-bold text-gray-900">MyNextPlat</h1>
                        </router-link>
                        <span class="ml-2 px-2 py-0.5 bg-gray-200 text-gray-600 text-xs font-medium rounded">Admin</span>
                        <div class="ml-8 flex space-x-1">
                            <router-link
                                to="/admin/games"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                    isActive('/admin/games')
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                                ]"
                            >
                                Games
                            </router-link>
                            <router-link
                                to="/admin/trophy-import"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                    isActive('/admin/trophy-import')
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                                ]"
                            >
                                Import URLs
                            </router-link>
                            <router-link
                                to="/admin/trophy-urls/unmatched"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                    isActive('/admin/trophy-urls/unmatched')
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                                ]"
                            >
                                Match URLs
                            </router-link>
                            <router-link
                                to="/admin/np-ids"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                    isActive('/admin/np-ids')
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                                ]"
                            >
                                NP IDs
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()

function isActive(path) {
    return route.path === path
}
</script>
