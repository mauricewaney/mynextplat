<template>
    <span class="inline-flex items-center gap-1">
        <img
            v-if="iconExists"
            :src="`/images/platforms/${slug}.svg`"
            :alt="fallback"
            :class="sizeClass"
        />
        <span v-if="!iconExists || label">{{ label || fallback }}</span>
    </span>
</template>

<script setup>
import { computed } from 'vue'

const KNOWN_SLUGS = ['ps5', 'ps4', 'ps3', 'ps-vita', 'ps-vr', 'ps-vr2']

const props = defineProps({
    slug: {
        type: String,
        required: true
    },
    fallback: {
        type: String,
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    sizeClass: {
        type: String,
        default: 'h-8'
    }
})

const iconExists = computed(() => KNOWN_SLUGS.includes(props.slug))
</script>
