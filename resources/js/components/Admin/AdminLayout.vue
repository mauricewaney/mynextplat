<template>
    <div class="min-h-screen bg-gray-100 dark:bg-slate-900">
        <!-- Top Navigation -->
        <nav class="bg-white dark:bg-slate-800 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-14">
                    <div class="flex items-center flex-1">
                        <router-link to="/" class="flex-shrink-0 flex items-center gap-2 group">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 group-hover:text-primary-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ appName }}</h1>
                        </router-link>
                        <span class="ml-2 px-2 py-0.5 bg-gray-200 dark:bg-slate-600 text-gray-600 dark:text-gray-300 text-xs font-medium rounded">Admin</span>
                        <div class="ml-8 flex space-x-1">
                            <router-link
                                to="/admin/games"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                    isActive('/admin/games')
                                        ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300'
                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                Games
                            </router-link>
                            <router-link
                                to="/admin/trophy-import"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                    isActive('/admin/trophy-import')
                                        ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300'
                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                Import URLs
                            </router-link>
                            <router-link
                                to="/admin/trophy-urls/unmatched"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                    isActive('/admin/trophy-urls/unmatched')
                                        ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300'
                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                Match URLs
                            </router-link>
                            <router-link
                                to="/admin/np-ids"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                    isActive('/admin/np-ids')
                                        ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300'
                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                NP IDs
                            </router-link>
                            <router-link
                                to="/admin/corrections"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5',
                                    isActive('/admin/corrections')
                                        ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300'
                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                Corrections
                                <span
                                    v-if="pendingCorrections > 0"
                                    class="px-1.5 py-0.5 text-xs font-bold rounded-full bg-yellow-400 text-yellow-900"
                                >
                                    {{ pendingCorrections }}
                                </span>
                            </router-link>
                            <router-link
                                to="/admin/contact"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5',
                                    isActive('/admin/contact')
                                        ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300'
                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                Inbox
                                <span
                                    v-if="pendingMessages > 0"
                                    class="px-1.5 py-0.5 text-xs font-bold rounded-full bg-yellow-400 text-yellow-900"
                                >
                                    {{ pendingMessages }}
                                </span>
                            </router-link>
                        </div>
                    </div>

                    <!-- Dark Mode Toggle + User Menu -->
                    <div class="flex items-center gap-2">
                        <button
                            @click="toggleDarkMode"
                            class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                            :title="darkMode ? 'Light mode' : 'Dark mode'"
                        >
                            <svg v-if="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </button>
                        <div class="relative user-menu-container">
                            <button
                                @click.stop="showUserMenu = !showUserMenu"
                                class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                            >
                                <img
                                    v-if="user?.avatar && !avatarError"
                                    :src="user.avatar"
                                    :alt="user.name"
                                    class="w-7 h-7 rounded-full"
                                    @error="avatarError = true"
                                />
                                <div v-else class="w-7 h-7 rounded-full bg-primary-600 flex items-center justify-center text-white text-xs font-medium">
                                    {{ user?.name?.charAt(0) || '?' }}
                                </div>
                                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                        to="/"
                                        @click="showUserMenu = false"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700"
                                    >
                                        Back to Site
                                    </router-link>
                                    <router-link
                                        to="/settings"
                                        @click="showUserMenu = false"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700"
                                    >
                                        Settings
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
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-2 sm:px-3 lg:px-4">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth'
import { useAppConfig } from '../../composables/useAppConfig'

const { appName } = useAppConfig()

const route = useRoute()
const router = useRouter()
const { user, logout } = useAuth()

const pendingCorrections = ref(0)
const pendingMessages = ref(0)
const showUserMenu = ref(false)
const avatarError = ref(false)
const darkMode = ref(false)

// Reset avatar error when user changes
watch(() => user.value?.avatar, () => {
    avatarError.value = false
})

function isActive(path) {
    return route.path === path
}

function toggleDarkMode() {
    darkMode.value = !darkMode.value
    document.documentElement.classList.toggle('dark', darkMode.value)
    localStorage.setItem('darkMode', darkMode.value ? 'true' : 'false')
}

async function handleLogout() {
    showUserMenu.value = false
    await logout()
    router.push('/')
}

// Close menu when clicking outside
function handleClickOutside(e) {
    if (!e.target.closest('.user-menu-container')) {
        showUserMenu.value = false
    }
}

async function loadCorrectionStats() {
    try {
        const response = await fetch('/api/admin/corrections/stats', { credentials: 'include' })
        if (response.ok) {
            const stats = await response.json()
            pendingCorrections.value = stats.pending || 0
        }
    } catch (e) {
        // Silently fail - badge just won't show
    }
}

async function loadMessageStats() {
    try {
        const response = await fetch('/api/admin/contact?status=pending&per_page=1', { credentials: 'include' })
        if (response.ok) {
            const data = await response.json()
            pendingMessages.value = data.total || 0
        }
    } catch (e) {
        // Silently fail
    }
}

onMounted(() => {
    // Initialize dark mode from localStorage (shared with frontend)
    const savedDark = localStorage.getItem('darkMode')
    if (savedDark === 'true' || (!savedDark && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        darkMode.value = true
        document.documentElement.classList.add('dark')
    }

    loadCorrectionStats()
    loadMessageStats()
    document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
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
