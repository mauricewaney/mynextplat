import { createRouter, createWebHistory } from 'vue-router'

// Public pages
import Home from '../pages/Home.vue'

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

    // Admin routes
    {
        path: '/admin',
        redirect: '/admin/games'
    },
    {
        path: '/admin/games',
        name: 'admin.games',
        component: GameList
    },
    {
        path: '/admin/trophy-import',
        name: 'admin.trophy-import',
        component: TrophyUrlImporter
    },
    {
        path: '/admin/trophy-urls/unmatched',
        name: 'admin.unmatched-urls',
        component: UnmatchedUrls
    },
    {
        path: '/admin/np-ids',
        name: 'admin.np-ids',
        component: NpIdManager
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router
