import { createRouter, createWebHistory } from 'vue-router'
import GameList from '../components/Admin/GameList.vue'

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
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router
