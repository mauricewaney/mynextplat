<template>
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg p-4">
        <!-- Subscribed state -->
        <div v-if="state === 'subscribed'" class="text-center py-2">
            <svg class="w-8 h-8 mx-auto mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p class="text-sm font-semibold text-gray-900 dark:text-white">You're subscribed</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">We'll email <span class="font-medium text-gray-700 dark:text-gray-300">{{ submittedEmail }}</span> when a guide is added.</p>
        </div>

        <!-- Form state -->
        <form v-else @submit.prevent="submit" class="space-y-2">
            <label :for="`notify-email-${gameId}`" class="block text-sm font-medium text-gray-900 dark:text-white">
                {{ headline }}
            </label>
            <p v-if="subline" class="text-xs text-gray-500 dark:text-gray-400">{{ subline }}</p>
            <div class="flex gap-2">
                <input
                    :id="`notify-email-${gameId}`"
                    v-model="email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="you@example.com"
                    class="flex-1 px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    :disabled="state === 'loading'"
                />
                <button
                    type="submit"
                    :disabled="state === 'loading' || !email"
                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
                >
                    <svg v-if="state === 'loading'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span v-else>Notify me</span>
                </button>
            </div>
            <p v-if="error" class="text-xs text-red-600 dark:text-red-400">{{ error }}</p>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    gameId: { type: Number, required: true },
    headline: { type: String, default: 'Get notified when a guide is added' },
    subline: { type: String, default: '' },
})

const email = ref('')
const submittedEmail = ref('')
const state = ref('idle') // 'idle' | 'loading' | 'subscribed'
const error = ref('')

async function submit() {
    if (!email.value) return
    state.value = 'loading'
    error.value = ''
    try {
        await axios.post('/api/notify', {
            email: email.value.trim(),
            game_id: props.gameId,
        })
        submittedEmail.value = email.value.trim()
        state.value = 'subscribed'
    } catch (e) {
        state.value = 'idle'
        if (e.response?.status === 429) {
            error.value = 'Too many requests. Try again in a minute.'
        } else if (e.response?.data?.errors?.email) {
            error.value = e.response.data.errors.email[0]
        } else {
            error.value = e.response?.data?.message || 'Something went wrong. Try again.'
        }
    }
}
</script>
