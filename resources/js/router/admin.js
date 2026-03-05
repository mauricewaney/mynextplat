import { createRouter, createWebHistory } from 'vue-router'

import GameList from '../components/Admin/GameList.vue'
import Corrections from '../components/Admin/Corrections.vue'
import TrophyUrlImporter from '../components/Admin/TrophyUrlImporter.vue'
import UnmatchedUrls from '../components/Admin/UnmatchedUrls.vue'
import NpIdManager from '../components/Admin/NpIdManager.vue'
import ContactInbox from '../pages/admin/ContactInbox.vue'
import DirectoryPages from '../components/Admin/DirectoryPages.vue'
import DirectoryPageEditor from '../components/Admin/DirectoryPageEditor.vue'
import FeaturedPlacements from '../components/Admin/FeaturedPlacements.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/admin', redirect: '/admin/games' },
        { path: '/admin/games', name: 'admin.games', component: GameList },
        { path: '/admin/trophy-import', name: 'admin.trophy-import', component: TrophyUrlImporter },
        { path: '/admin/trophy-urls/unmatched', name: 'admin.unmatched-urls', component: UnmatchedUrls },
        { path: '/admin/np-ids', name: 'admin.np-ids', component: NpIdManager },
        { path: '/admin/corrections', name: 'admin.corrections', component: Corrections },
        { path: '/admin/contact', name: 'admin.contact', component: ContactInbox },
        { path: '/admin/directory-pages', name: 'admin.directory-pages', component: DirectoryPages },
        { path: '/admin/directory-pages/:id', name: 'admin.directory-page-editor', component: DirectoryPageEditor },
        { path: '/admin/featured', name: 'admin.featured', component: FeaturedPlacements },
    ]
})

export default router
