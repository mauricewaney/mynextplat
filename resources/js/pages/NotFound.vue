<template>
    <AppLayout title="Page Not Found">
        <div class="max-w-3xl mx-auto px-4 py-16 text-center">
            <div class="mb-8">
                <div class="text-8xl font-bold bg-gradient-to-r from-primary-500 to-purple-500 bg-clip-text text-transparent mb-4">
                    404
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                    Page Not Found
                </h1>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    The page you're looking for doesn't exist or may have been moved. Let's get you back on track.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <router-link
                    to="/"
                    class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-sm transition-colors"
                >
                    Browse Games
                </router-link>
                <router-link
                    to="/profiles"
                    class="px-6 py-2.5 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 font-medium rounded-lg shadow-sm hover:shadow-md transition-all"
                >
                    View Libraries
                </router-link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useHead } from '@vueuse/head'
import AppLayout from '../components/AppLayout.vue'
import { useAppConfig } from '../composables/useAppConfig'

const { appName } = useAppConfig()

useHead({
    title: `Page Not Found | ${appName}`,
    meta: [
        { name: 'robots', content: 'noindex' },
    ],
})
</script>
