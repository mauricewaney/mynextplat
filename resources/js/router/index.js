import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { loadUserGameIds } from '../composables/useUserGames'

// Public pages
import Home from '../pages/Home.vue'
import MyGames from '../pages/MyGames.vue'
import GameDetail from '../pages/GameDetail.vue'
import ReportIssue from '../pages/ReportIssue.vue'
import Contact from '../pages/Contact.vue'
import PrivacyPolicy from '../pages/PrivacyPolicy.vue'
import Settings from '../pages/Settings.vue'
import Profile from '../pages/Profile.vue'
import Profiles from '../pages/Profiles.vue'
import NotFound from '../pages/NotFound.vue'

// Admin pages
import GameList from '../components/Admin/GameList.vue'
import Corrections from '../components/Admin/Corrections.vue'
import TrophyUrlImporter from '../components/Admin/TrophyUrlImporter.vue'
import UnmatchedUrls from '../components/Admin/UnmatchedUrls.vue'
import NpIdManager from '../components/Admin/NpIdManager.vue'
import ContactInbox from '../pages/admin/ContactInbox.vue'

const routes = [
    // Public routes
    {
        path: '/',
        name: 'home',
        component: Home
    },
    {
        path: '/game/:slug',
        name: 'game-detail',
        component: GameDetail
    },
    {
        path: '/report-issue',
        name: 'report-issue',
        component: ReportIssue
    },
    {
        path: '/contact',
        name: 'contact',
        component: Contact
    },
    {
        path: '/privacy',
        name: 'privacy',
        component: PrivacyPolicy
    },

    // Public profiles directory
    {
        path: '/profiles',
        name: 'profiles',
        component: Profiles
    },

    // Public profile
    {
        path: '/u/:identifier',
        name: 'profile',
        component: Profile
    },

    // Authenticated routes
    {
        path: '/my-games',
        name: 'my-games',
        component: MyGames,
        meta: { requiresAuth: true }
    },
    {
        path: '/settings',
        name: 'settings',
        component: Settings,
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
    },
    {
        path: '/admin/corrections',
        name: 'admin.corrections',
        component: Corrections,
        meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/contact',
        name: 'admin.contact',
        component: ContactInbox,
        meta: { requiresAuth: true, requiresAdmin: true }
    },

    // 404 catch-all (must be last)
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: NotFound
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
        // Pre-load user's game IDs into shared cache (non-blocking)
        if (isAuthenticated.value) {
            loadUserGameIds()
        }
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
