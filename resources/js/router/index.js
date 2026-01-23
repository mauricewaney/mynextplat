import { createRouter, createWebHistory } from 'vue-router'
import GameList from '../components/Admin/GameList.vue'
import TrophyUrlImporter from '../components/Admin/TrophyUrlImporter.vue'
import UnmatchedUrls from '../components/Admin/UnmatchedUrls.vue'

const routes = [
    {
        path: '/',
        redirect: '/admin/games'
    },
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
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router
