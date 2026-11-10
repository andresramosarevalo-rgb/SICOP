<script setup lang="ts">
defineProps<{
    message?: string;
    reservarEspacio?: boolean;
}>();
</script>

<template>
    <div
        v-show="message || reservarEspacio"
        :class="{ 'min-h-10': reservarEspacio }"
        :aria-live="reservarEspacio ? 'polite' : undefined"
    >
        <p class="text-sm text-red-600 dark:text-red-500">
            {{ message }}
        </p>
    </div>
</template>
