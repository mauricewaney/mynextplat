<template>
    <AppLayout title="Privacy Policy">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Privacy Policy</h1>

            <div class="prose prose-gray dark:prose-invert max-w-none space-y-8">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    Last updated: {{ new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                </p>

                <!-- Intro -->
                <section>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ appName }} is a PlayStation trophy guide aggregator and tracker. This privacy policy explains what data we collect, how we use it, and your rights regarding that data.
                    </p>
                </section>

                <!-- Data Collected -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Data We Collect</h2>

                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mt-4 mb-2">Google OAuth Sign-In</h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        When you sign in with Google, we receive and store your name, email address, and profile picture. We use this information solely to identify your account and display your profile.
                    </p>

                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mt-4 mb-2">Contact Form</h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        If you submit a message through our contact form, we store your name, email address, and message content so we can respond to your inquiry.
                    </p>

                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mt-4 mb-2">Automatically Collected Data</h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        Like most websites, our server logs may record your IP address, browser type, and pages visited. This data is used for security monitoring and debugging purposes.
                    </p>

                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mt-4 mb-2">Cookies</h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        We use essential cookies for session management and authentication. Google reCAPTCHA, used on our contact form, may also set cookies as part of its bot detection. We do not use advertising or tracking cookies.
                    </p>
                </section>

                <!-- Data Usage -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">How We Use Your Data</h2>
                    <ul class="list-disc pl-5 space-y-2 text-gray-700 dark:text-gray-300">
                        <li>To authenticate and manage your account</li>
                        <li>To save and display your game library and preferences</li>
                        <li>To send email notifications about new trophy guides (if you opt in)</li>
                        <li>To respond to contact form submissions</li>
                        <li>To improve and maintain the service</li>
                    </ul>
                </section>

                <!-- Third-Party Services -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Third-Party Services</h2>
                    <ul class="list-disc pl-5 space-y-2 text-gray-700 dark:text-gray-300">
                        <li><strong>Google OAuth</strong> &mdash; Used for account sign-in. Subject to <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="text-primary-600 dark:text-primary-400 hover:underline">Google's Privacy Policy</a>.</li>
                        <li><strong>Google reCAPTCHA</strong> &mdash; Used on the contact form to prevent spam. Subject to Google's <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="text-primary-600 dark:text-primary-400 hover:underline">Privacy Policy</a> and <a href="https://policies.google.com/terms" target="_blank" rel="noopener" class="text-primary-600 dark:text-primary-400 hover:underline">Terms of Service</a>.</li>
                    </ul>
                    <p class="text-gray-700 dark:text-gray-300 mt-3">
                        We do not sell or share your personal data with any other third parties.
                    </p>
                </section>

                <!-- Data Retention -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Data Retention</h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        Your account data is retained as long as your account is active. Contact form submissions are retained as long as needed to resolve your inquiry. Server logs are retained for a limited period for security purposes.
                    </p>
                </section>

                <!-- User Rights -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Your Rights</h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        You have the right to:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-gray-700 dark:text-gray-300">
                        <li>Access the personal data we hold about you</li>
                        <li>Request correction of inaccurate data</li>
                        <li>Request deletion of your account and associated data</li>
                        <li>Opt out of email notifications at any time via your settings</li>
                    </ul>
                </section>

                <!-- Contact -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Contact</h2>
                    <p class="text-gray-700 dark:text-gray-300">
                        If you have questions about this privacy policy or wish to exercise your data rights, please reach out through our
                        <router-link to="/contact" class="text-primary-600 dark:text-primary-400 hover:underline">contact page</router-link>.
                    </p>
                </section>
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
    title: `Privacy Policy | ${appName}`,
    meta: [
        { name: 'description', content: `${appName} privacy policy. Learn about what data we collect and how we use it.` },
    ],
})
</script>
