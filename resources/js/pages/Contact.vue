<template>
    <AppLayout title="Contact Us">
        <div class="max-w-3xl mx-auto px-4 py-8">
            <!-- Success Message -->
            <div v-if="submitted" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-8 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 dark:bg-emerald-900/50 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Message Sent!</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Thank you for reaching out. We'll get back to you as soon as possible.
                </p>
                <div class="flex justify-center gap-3">
                    <router-link
                        to="/"
                        class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors"
                    >
                        Back to Home
                    </router-link>
                    <button
                        @click="resetForm"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors"
                    >
                        Send Another Message
                    </button>
                </div>
            </div>

            <!-- Form -->
            <form v-else @submit.prevent="submitForm" class="space-y-6">
                <!-- Info Box -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Have a question, found a bug, or want to suggest a feature? Send us a message and we'll get back to you.
                        </p>
                    </div>
                </div>

                <!-- Name & Email -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Your Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="John Doe"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            maxlength="255"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Your Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="your@email.com"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            maxlength="255"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            We'll use this to respond to your message
                        </p>
                    </div>
                </div>

                <!-- Category -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button
                            v-for="(label, key) in categories"
                            :key="key"
                            type="button"
                            @click="form.category = key"
                            :class="[
                                'px-4 py-3 rounded-lg text-sm font-medium transition-all text-center',
                                form.category === key
                                    ? 'bg-primary-600 text-white ring-2 ring-primary-600 ring-offset-2 dark:ring-offset-slate-800'
                                    : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600'
                            ]"
                        >
                            {{ label }}
                        </button>
                    </div>
                </div>

                <!-- Subject -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Subject <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.subject"
                        type="text"
                        placeholder="Brief summary of your message"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        maxlength="255"
                    />
                </div>

                <!-- Message -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Message <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="form.message"
                        rows="6"
                        placeholder="Tell us what's on your mind..."
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
                        maxlength="5000"
                    ></textarea>
                    <div class="mt-1 text-xs text-gray-400 text-right">{{ form.message.length }}/5000</div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-red-700 dark:text-red-300">{{ error }}</p>
                    </div>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="!canSubmit || submitting"
                    class="w-full py-4 bg-primary-600 text-white rounded-xl font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <svg v-if="submitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>{{ submitting ? 'Sending...' : 'Send Message' }}</span>
                </button>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useHead } from '@vueuse/head'
import { useAuth } from '../composables/useAuth'
import { useAppConfig } from '../composables/useAppConfig'
import AppLayout from '../components/AppLayout.vue'

const { appName } = useAppConfig()

useHead({
    title: `Contact Us | ${appName}`,
    meta: [
        { name: 'description', content: 'Get in touch with the MyNextPlat team. Report bugs, suggest features, or ask questions about PlayStation trophy guides.' },
        { property: 'og:title', content: `Contact Us | ${appName}` },
        { property: 'og:description', content: 'Get in touch with the MyNextPlat team. Report bugs, suggest features, or ask questions.' },
    ],
})

let recaptchaScript = null
const { isAuthenticated, user } = useAuth()

const categories = {
    general: 'General',
    bug_report: 'Bug Report',
    suggestion: 'Suggestion',
    other: 'Other',
}

const submitting = ref(false)
const submitted = ref(false)
const error = ref('')

const form = reactive({
    name: '',
    email: '',
    category: '',
    subject: '',
    message: '',
})

const canSubmit = computed(() => {
    return form.name.trim()
        && form.email.trim()
        && form.category
        && form.subject.trim()
        && form.message.length >= 10
})

async function getRecaptchaToken() {
    if (isAuthenticated.value) return null

    const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY
    if (!siteKey) return null

    return new Promise((resolve) => {
        if (typeof grecaptcha === 'undefined') {
            resolve(null)
            return
        }
        grecaptcha.ready(() => {
            grecaptcha.execute(siteKey, { action: 'submit_contact' })
                .then(resolve)
                .catch(() => resolve(null))
        })
    })
}

async function submitForm() {
    if (!canSubmit.value || submitting.value) return

    submitting.value = true
    error.value = ''

    try {
        const recaptchaToken = await getRecaptchaToken()

        const payload = {
            name: form.name,
            email: form.email,
            category: form.category,
            subject: form.subject,
            message: form.message,
        }

        if (recaptchaToken) {
            payload.recaptcha_token = recaptchaToken
        }

        const response = await fetch('/api/contact', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(payload),
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.message || 'Failed to send message')
        }

        submitted.value = true
    } catch (e) {
        error.value = e.message
    } finally {
        submitting.value = false
    }
}

function resetForm() {
    form.name = isAuthenticated.value ? (user.value?.name || '') : ''
    form.email = isAuthenticated.value ? (user.value?.email || '') : ''
    form.category = ''
    form.subject = ''
    form.message = ''
    submitted.value = false
    error.value = ''
}

function loadRecaptcha() {
    const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY
    if (!siteKey || isAuthenticated.value) return

    if (document.querySelector('script[src*="recaptcha"]')) return

    recaptchaScript = document.createElement('script')
    recaptchaScript.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`
    recaptchaScript.async = true
    recaptchaScript.defer = true
    document.head.appendChild(recaptchaScript)
}

function unloadRecaptcha() {
    if (recaptchaScript && recaptchaScript.parentNode) {
        recaptchaScript.parentNode.removeChild(recaptchaScript)
    }
    const badge = document.querySelector('.grecaptcha-badge')
    if (badge) {
        badge.parentNode.removeChild(badge)
    }
    if (window.grecaptcha) {
        delete window.grecaptcha
    }
}

onMounted(() => {
    // Auto-fill name/email if logged in
    if (isAuthenticated.value && user.value) {
        form.name = user.value.name || ''
        form.email = user.value.email || ''
    }
    loadRecaptcha()
})

onUnmounted(() => {
    unloadRecaptcha()
})
</script>
