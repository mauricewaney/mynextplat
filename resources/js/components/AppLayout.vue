<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-primary-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 transition-colors duration-300 flex flex-col">
        <!-- Header -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-lg border-b border-gray-200 shadow-sm dark:bg-slate-900/95 dark:border-slate-700/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-4">
                        <router-link to="/" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                            <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </router-link>

                        <slot name="nav-tabs">
                            <!-- Navigation Tabs - Mobile (default) -->
                            <div class="sm:hidden flex bg-gray-100 dark:bg-slate-800 rounded-lg p-0.5">
                                <router-link
                                    to="/"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-xs font-medium transition-colors',
                                        $route.path === '/'
                                            ? 'bg-primary-600 text-white shadow-sm'
                                            : 'text-gray-600 dark:text-gray-400'
                                    ]"
                                >
                                    All Games
                                </router-link>
                                <router-link
                                    v-if="isAuthenticated"
                                    to="/my-games"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-xs font-medium transition-colors',
                                        $route.path === '/my-games'
                                            ? 'bg-primary-600 text-white shadow-sm'
                                            : 'text-gray-600 dark:text-gray-400'
                                    ]"
                                >
                                    My Games
                                </router-link>
                            </div>

                            <!-- Navigation Tabs - Desktop (default) -->
                            <div class="hidden sm:flex items-center">
                                <div class="flex bg-gray-100 dark:bg-slate-800 rounded-lg p-1">
                                    <router-link
                                        to="/"
                                        :class="[
                                            'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                                            $route.path === '/'
                                                ? 'bg-primary-600 text-white shadow-sm'
                                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-700/50'
                                        ]"
                                    >
                                        All Games
                                    </router-link>
                                    <router-link
                                        v-if="isAuthenticated"
                                        to="/my-games"
                                        :class="[
                                            'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                                            $route.path === '/my-games'
                                                ? 'bg-primary-600 text-white shadow-sm'
                                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-700/50'
                                        ]"
                                    >
                                        My Games
                                    </router-link>
                                </div>
                            </div>
                        </slot>
                    </div>

                    <!-- Mobile actions -->
                    <div class="lg:hidden flex items-center gap-1">
                        <!-- Slot for mobile-specific actions -->
                        <slot name="header-mobile"></slot>

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
                        <UserMenuDropdown
                            v-if="isAuthenticated"
                            :show-chevron="false"
                            button-class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors shrink-0"
                            avatar-class="w-8 h-8 rounded-full object-cover aspect-square"
                            fallback-avatar-class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-medium aspect-square shrink-0"
                        />
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
                    </div>

                    <!-- Desktop actions -->
                    <div class="hidden lg:flex items-center gap-4 text-sm">
                        <router-link
                            to="/contact"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-medium transition-colors"
                        >
                            Contact
                        </router-link>
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
                        <UserMenuDropdown v-if="isAuthenticated" />

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

        <!-- Main Content -->
        <main class="flex-1">
            <slot></slot>
        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-gray-200 dark:border-slate-700/50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Brand -->
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <div class="w-6 h-6 bg-primary-600 rounded-md flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium">{{ appName }}</span>
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
                        <!-- Donation Link (toggle via env) -->
                        <a
                            v-if="showDonations"
                            :href="donationUrl"
                            target="_blank"
                            class="flex items-center gap-1 px-2 py-1 bg-pink-50 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 rounded-full hover:bg-pink-100 dark:hover:bg-pink-900/50 transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            <span class="font-medium">Support</span>
                        </a>
                        <router-link to="/profiles" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            Profiles
                        </router-link>
                        <router-link to="/contact" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            Contact
                        </router-link>
                        <router-link to="/privacy" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            Privacy
                        </router-link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useAppConfig } from '../composables/useAppConfig'
import UserMenuDropdown from './UserMenuDropdown.vue'

const { appName } = useAppConfig()

const props = defineProps({
    title: {
        type: String,
        default: ''
    }
})

const { user, isAuthenticated, isAdmin, loginWithGoogle, logout } = useAuth()

// Dark mode
const darkMode = ref(false)

function toggleDarkMode() {
    darkMode.value = !darkMode.value
    document.documentElement.classList.toggle('dark', darkMode.value)
    localStorage.setItem('darkMode', darkMode.value ? 'true' : 'false')
}


// Donations config
const showDonations = import.meta.env.VITE_SHOW_DONATIONS === 'true'
const donationUrl = import.meta.env.VITE_DONATION_URL || 'https://ko-fi.com/mynextplat'

onMounted(() => {
    // Initialize dark mode
    const savedDark = localStorage.getItem('darkMode')
    if (savedDark === 'true' || (!savedDark && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        darkMode.value = true
        document.documentElement.classList.add('dark')
    }

})
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.15s ease-out;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
