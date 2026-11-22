<script setup lang="ts">
withDefaults(defineProps<{ formulario?: boolean }>(), { formulario: false });
</script>

<template>
    <div
        class="mx-auto flex w-full min-w-0 flex-1 flex-col gap-7 px-4 pt-7 pb-12 md:px-10"
        :class="formulario ? 'max-w-4xl' : 'max-w-[1280px]'"
    >
        <slot />
    </div>
</template>
