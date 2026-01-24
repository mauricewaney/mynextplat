<template>
    <div class="border-b border-gray-100 last:border-0">
        <button
            @click="isOpen = !isOpen"
            class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors"
        >
            <div class="flex items-center gap-2">
                <span class="font-medium text-gray-900">{{ title }}</span>
                <span
                    v-if="count"
                    class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-medium rounded-full"
                >
                    {{ count }}
                </span>
                <span
                    v-else-if="badge"
                    class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-medium rounded-full"
                >
                    {{ badge }}
                </span>
            </div>
            <svg
                :class="['w-5 h-5 text-gray-400 transition-transform', isOpen ? 'rotate-180' : '']"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div
            v-show="isOpen"
            class="px-4 pb-4"
        >
            <slot />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    count: {
        type: Number,
        default: 0
    },
    badge: {
        type: String,
        default: null
    },
    collapsed: {
        type: Boolean,
        default: false
    }
})

const isOpen = ref(!props.collapsed)
</script>
