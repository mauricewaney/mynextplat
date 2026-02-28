import './bootstrap'
import { createApp } from 'vue'
import '../css/app.css'

// Seed auth from server data (no API call needed)
import { seedAuth } from './composables/useAuth'
if (window.__AUTH_USER__) seedAuth(window.__AUTH_USER__)

// Mount NavMenu on every page
import NavMenu from './components/NavMenu.vue'
const navEl = document.getElementById('nav-menu')
if (navEl) createApp(NavMenu).mount(navEl)

// Conditional page component mounting
const mounts = {
    'vue-home': () => import('./pages/Home.vue'),
    'vue-game-detail': () => import('./pages/GameDetail.vue'),
    'vue-my-games': () => import('./pages/MyGames.vue'),
    'vue-settings': () => import('./pages/Settings.vue'),
    'vue-contact': () => import('./pages/Contact.vue'),
    'vue-profiles': () => import('./pages/Profiles.vue'),
    'vue-profile': () => import('./pages/Profile.vue'),
    'vue-report-issue': () => import('./pages/ReportIssue.vue'),
    'vue-privacy': () => import('./pages/PrivacyPolicy.vue'),
}

for (const [id, loader] of Object.entries(mounts)) {
    const el = document.getElementById(id)
    if (el) {
        loader().then(mod => {
            const app = createApp(mod.default, { ...el.dataset })
            app.mount(el)
        })
    }
}

// Admin island — self-contained SPA with its own vue-router
const adminEl = document.getElementById('vue-admin')
if (adminEl) {
    Promise.all([
        import('./pages/AdminApp.vue'),
        import('./router/admin.js')
    ]).then(([mod, routerMod]) => {
        const app = createApp(mod.default)
        app.use(routerMod.default)
        app.mount(adminEl)
    })
}
