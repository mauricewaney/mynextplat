<template>
    <div class="relative user-menu-container">
        <button
            @click.stop="showMenu = !showMenu"
            :class="buttonClass"
        >
            <img
                v-if="user?.avatar && !avatarError"
                :src="user.avatar"
                :alt="user.name"
                :class="avatarClass"
                @error="avatarError = true"
            />
            <div v-else :class="fallbackAvatarClass">
                {{ user?.name?.charAt(0) || '?' }}
            </div>
            <svg v-if="showChevron" class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <Transition name="dropdown">
            <div
                v-if="showMenu"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-gray-200 dark:border-slate-700 py-1 z-50"
            >
                <div class="px-4 py-2 border-b border-gray-100 dark:border-slate-700">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user?.email }}</p>
                </div>
                <router-link
                    to="/my-games"
                    @click="showMenu = false"
                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700"
                >
                    My Games
                </router-link>
                <router-link
                    v-if="isAdmin"
                    to="/admin"
                    @click="showMenu = false"
                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700"
                >
                    Admin
                </router-link>
                <router-link
                    to="/settings"
                    @click="showMenu = false"
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
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuth } from '../composables/useAuth'

const props = defineProps({
    showChevron: {
        type: Boolean,
        default: true
    },
    buttonClass: {
        type: String,
        default: 'flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors'
    },
    avatarClass: {
        type: String,
        default: 'w-8 h-8 rounded-full'
    },
    fallbackAvatarClass: {
        type: String,
        default: 'w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-medium'
    }
})

const { user, isAdmin, logout } = useAuth()

const showMenu = ref(false)
const avatarError = ref(false)

async function handleLogout() {
    showMenu.value = false
    await logout()
    window.location.href = '/'
}

function handleClickOutside(e) {
    if (!e.target.closest('.user-menu-container')) {
        showMenu.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
