<template>
    <button
        v-if="isAuthenticated"
        @click.stop="toggle"
        :disabled="loading"
        :class="[
            'p-1.5 rounded-lg transition-all',
            inList
                ? 'bg-primary-600 text-white hover:bg-primary-700'
                : 'bg-white/90 dark:bg-slate-800/90 text-gray-600 dark:text-gray-400 hover:bg-white dark:hover:bg-slate-800 hover:text-primary-600 dark:hover:text-primary-400 shadow-sm',
            loading ? 'opacity-50 cursor-wait' : ''
        ]"
        :title="inList ? 'Remove from list' : 'Add to list'"
    >
        <!-- Loading -->
        <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <!-- Check icon (in list) -->
        <svg v-else-if="inList" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <!-- Plus icon (not in list) -->
        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
    </button>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useUserGames } from '../composables/useUserGames'

const props = defineProps({
    gameId: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['added', 'removed'])

const { isAuthenticated } = useAuth()
const { addToList, removeFromList, checkInList } = useUserGames()

const inList = ref(false)
const loading = ref(false)

async function checkStatus() {
    if (!isAuthenticated.value) return

    try {
        const result = await checkInList(props.gameId)
        inList.value = result.in_list
    } catch (e) {
        console.error('Failed to check list status:', e)
    }
}

async function toggle() {
    if (loading.value) return

    loading.value = true

    try {
        if (inList.value) {
            await removeFromList(props.gameId)
            inList.value = false
            emit('removed', props.gameId)
        } else {
            await addToList(props.gameId)
            inList.value = true
            emit('added', props.gameId)
        }
    } catch (e) {
        console.error('Failed to toggle list status:', e)
    } finally {
        loading.value = false
    }
}

// Check status when authenticated state changes
watch(isAuthenticated, (newVal) => {
    if (newVal) {
        checkStatus()
    } else {
        inList.value = false
    }
})

// Check status on mount if authenticated
onMounted(() => {
    if (isAuthenticated.value) {
        checkStatus()
    }
})
</script>
