<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
        <div class="flex items-center justify-center min-h-screen px-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

            <!-- Modal -->
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
                <!-- Header -->
                <div class="border-b px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
                </div>

                <!-- Content -->
                <div class="px-6 py-4 max-h-96 overflow-y-auto">
                    <slot></slot>
                </div>

                <!-- Footer -->
                <div class="border-t px-6 py-4 flex justify-end space-x-3">
                    <button
                        @click="close"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirm"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: {
        type: Boolean,
        required: true
    },
    title: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['close', 'confirm']);

function close() {
    emit('close');
}

function confirm() {
    emit('confirm');
}
</script>
