<script setup lang="ts">
defineProps<{ titulo: string; descripcion?: string }>();
</script>

<template>
    <section
        class="min-w-0 overflow-hidden rounded-xl border bg-card shadow-sm"
    >
        <header class="space-y-1 border-b px-5 py-4">
            <h2 class="text-base font-semibold">{{ titulo }}</h2>
            <p v-if="descripcion" class="text-sm text-muted-foreground">
                {{ descripcion }}
            </p>
        </header>
        <div
            class="tabla-pos overflow-x-auto focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-ring"
            role="region"
            :aria-label="titulo"
            tabindex="0"
        >
            <slot />
        </div>
    </section>
</template>

<style scoped>
.tabla-pos :deep(table) {
    width: 100%;
    min-width: 42rem;
    font-size: 0.875rem;
}
.tabla-pos :deep(thead) {
    background: var(--secondary);
    color: var(--muted-foreground);
}
.tabla-pos :deep(th),
.tabla-pos :deep(td) {
    padding: 0.875rem 1rem;
}
.tabla-pos :deep(th) {
    font-weight: 600;
    white-space: nowrap;
}
.tabla-pos :deep(tbody tr:hover) {
    background: color-mix(in srgb, var(--muted) 50%, transparent);
}
.tabla-pos :deep(td[colspan].text-center) {
    padding: 2.5rem 1rem;
}
/* El mensaje vacío debe poder leerse sin desplazar una tabla sin registros. */
.tabla-pos :deep(table:has(tbody > tr:only-child > td[colspan].text-center)) {
    min-width: 0;
}
.tabla-pos
    :deep(table:has(tbody > tr:only-child > td[colspan].text-center) thead) {
    display: none;
}
</style>
