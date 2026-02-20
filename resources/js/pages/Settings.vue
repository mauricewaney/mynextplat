<template>
    <AppLayout title="Settings">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">Settings</h1>

            <!-- Loading -->
            <div v-if="loading" class="space-y-6">
                <div v-for="n in 3" :key="n" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6 animate-pulse">
                    <div class="h-5 bg-gray-200 dark:bg-slate-700 rounded w-1/3 mb-4"></div>
                    <div class="h-10 bg-gray-200 dark:bg-slate-700 rounded w-full"></div>
                </div>
            </div>

            <div v-else class="space-y-6">
                <!-- Profile Visibility -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Public Profile</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Allow others to view your game library
                            </p>
                        </div>
                        <button
                            @click="togglePublic"
                            :disabled="saving"
                            :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                settings.profile_public ? 'bg-primary-600' : 'bg-gray-200 dark:bg-slate-600'
                            ]"
                        >
                            <span
                                :class="[
                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                    settings.profile_public ? 'translate-x-6' : 'translate-x-1'
                                ]"
                            />
                        </button>
                    </div>

                    <!-- Share Link (only when public) -->
                    <div v-if="settings.profile_public" class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-700">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Your profile link
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                :value="profileUrl"
                                readonly
                                class="flex-1 px-3 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-sm text-gray-700 dark:text-gray-300"
                            />
                            <button
                                @click="copyLink"
                                class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors flex items-center gap-2"
                            >
                                <svg v-if="copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                {{ copied ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Profile Display Name -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Collection Name</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        The title of your game collection (e.g., "Retro Games", "PS5 Platinums")
                    </p>

                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input
                                v-model="profileNameInput"
                                type="text"
                                placeholder="My Game Collection"
                                maxlength="50"
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                        <button
                            @click="saveProfileName"
                            :disabled="saving || !profileNameInput || profileNameInput.trim().length < 2 || profileNameInput === settings.profile_name"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Save
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                        URL preview: {{ baseUrl }}/u/<span class="text-primary-600 dark:text-primary-400">{{ generatedSlug || 'your-name' }}</span>
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        2-50 characters. The URL slug is auto-generated from your collection name.
                    </p>
                </div>

                <!-- Email Notifications -->
                <div v-if="settings.mail_enabled" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Email Notifications</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Get notified when guides are added for games in your list
                            </p>
                        </div>
                        <button
                            @click="toggleNotifications"
                            :disabled="saving"
                            :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                settings.notify_new_guides ? 'bg-primary-600' : 'bg-gray-200 dark:bg-slate-600'
                            ]"
                        >
                            <span
                                :class="[
                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                    settings.notify_new_guides ? 'translate-x-6' : 'translate-x-1'
                                ]"
                            />
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">
                        Emails sent to: {{ settings.email }}
                    </p>
                </div>

                <!-- Account Info (read-only) -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Account</h2>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Name</span>
                            <span class="text-gray-900 dark:text-white">{{ settings.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Email</span>
                            <span class="text-gray-900 dark:text-white">{{ settings.email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Sign in method</span>
                            <span class="text-gray-900 dark:text-white flex items-center gap-1">
                                <svg class="w-4 h-4" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                Google
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Message -->
                <Transition name="fade">
                    <div v-if="message" :class="[
                        'p-4 rounded-lg text-sm',
                        messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'
                    ]">
                        {{ message }}
                    </div>
                </Transition>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useHead } from '@vueuse/head'
import AppLayout from '../components/AppLayout.vue'
import { useAppConfig } from '../composables/useAppConfig'

const { appName } = useAppConfig()

useHead({
    title: `Settings | ${appName}`,
    meta: [
        { name: 'robots', content: 'noindex, nofollow' },
    ],
})

const loading = ref(true)
const saving = ref(false)
const settings = ref({
    profile_public: false,
    profile_slug: '',
    profile_name: '',
    profile_url: '',
    notify_new_guides: false,
    email: '',
    name: '',
})

const profileNameInput = ref('')
const copied = ref(false)
const message = ref('')
const messageType = ref('success')

const baseUrl = computed(() => window.location.origin)
const profileUrl = computed(() => `${baseUrl.value}/u/${settings.value.profile_slug}`)

const generatedSlug = computed(() => {
    const name = profileNameInput.value?.trim()
    if (!name) return ''
    // Simple client-side slug preview (mirrors Str::slug behavior)
    return name
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s-]+/g, '-')
        .replace(/^-+|-+$/g, '')
        || 'your-name'
})

async function loadSettings() {
    try {
        const response = await fetch('/api/settings', {
            credentials: 'include',
        })
        if (response.ok) {
            const data = await response.json()
            settings.value = data
            profileNameInput.value = data.profile_name || ''
        }
    } catch (e) {
        console.error('Failed to load settings:', e)
    } finally {
        loading.value = false
    }
}

async function updateSetting(key, value) {
    saving.value = true
    message.value = ''

    try {
        const response = await fetch('/api/settings', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({ [key]: value }),
        })

        const data = await response.json()

        if (!response.ok) {
            const errors = data.errors
            if (errors) {
                throw new Error(Object.values(errors).flat().join(' '))
            }
            throw new Error(data.message || 'Failed to update settings')
        }

        // Update local state
        if (data.profile_public !== undefined) settings.value.profile_public = data.profile_public
        if (data.profile_slug !== undefined) settings.value.profile_slug = data.profile_slug
        if (data.profile_name !== undefined) {
            settings.value.profile_name = data.profile_name
            profileNameInput.value = data.profile_name
        }
        if (data.notify_new_guides !== undefined) settings.value.notify_new_guides = data.notify_new_guides

        message.value = 'Settings saved'
        messageType.value = 'success'
        setTimeout(() => { message.value = '' }, 3000)
    } catch (e) {
        message.value = e.message
        messageType.value = 'error'
    } finally {
        saving.value = false
    }
}

function togglePublic() {
    updateSetting('profile_public', !settings.value.profile_public)
}

function toggleNotifications() {
    updateSetting('notify_new_guides', !settings.value.notify_new_guides)
}

function saveProfileName() {
    const name = profileNameInput.value?.trim()
    if (name && name.length >= 2) {
        updateSetting('profile_name', name)
    }
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(profileUrl.value)
        copied.value = true
        setTimeout(() => { copied.value = false }, 2000)
    } catch (e) {
        console.error('Failed to copy:', e)
    }
}

onMounted(loadSettings)
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
