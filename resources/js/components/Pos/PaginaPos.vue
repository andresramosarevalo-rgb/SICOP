<script setup lang="ts">
withDefaults(defineProps<{ formulario?: boolean }>(), { formulario: false });
</script>

<template>
    <div
        class="mx-auto flex w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
        :class="formulario ? 'max-w-4xl' : 'max-w-7xl'"
    >
        <slot />
    </div>
</template>
