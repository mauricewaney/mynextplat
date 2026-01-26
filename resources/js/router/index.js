import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'

// Public pages
import Home from '../pages/Home.vue'
import MyGames from '../pages/MyGames.vue'

// Admin pages
import GameList from '../components/Admin/GameList.vue'
import TrophyUrlImporter from '../components/Admin/TrophyUrlImporter.vue'
import UnmatchedUrls from '../components/Admin/UnmatchedUrls.vue'
import NpIdManager from '../components/Admin/NpIdManager.vue'

const routes = [
    // Public routes
    {
        path: '/',
        name: 'home',
        component: Home
    },

    // Authenticated routes
    {
        path: '/my-games',
        name: 'my-games',
        component: MyGames,
        meta: { requiresAuth: true }
    },

    // Admin routes
    {
        path: '/admin',
        redirect: '/admin/games',
        meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/games',
        name: 'admin.games',
        component: GameList,
        meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/trophy-import',
        name: 'admin.trophy-import',
        component: TrophyUrlImporter,
        meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/trophy-urls/unmatched',
        name: 'admin.unmatched-urls',
        component: UnmatchedUrls,
        meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/np-ids',
        name: 'admin.np-ids',
        component: NpIdManager,
        meta: { requiresAuth: true, requiresAdmin: true }
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// Navigation guard
router.beforeEach(async (to, from, next) => {
    const { isAuthenticated, isAdmin, initAuth, initialized } = useAuth()

    // Ensure auth state is initialized
    if (!initialized.value) {
        await initAuth()
    }

    // Check if route requires auth
    if (to.meta.requiresAuth && !isAuthenticated.value) {
        // Redirect to home with a flag to show login prompt
        return next({ path: '/', query: { login: 'required' } })
    }

    // Check if route requires admin
    if (to.meta.requiresAdmin && !isAdmin.value) {
        // Redirect to home
        return next({ path: '/' })
    }

    next()
})

export default router
