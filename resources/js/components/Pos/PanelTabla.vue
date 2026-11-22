<script setup lang="ts">
withDefaults(
    defineProps<{
        titulo: string;
        descripcion?: string;
        indice?: number;
        relleno?: string;
        ocultarTitulo?: boolean;
    }>(),
    { indice: 2, relleno: '14px', ocultarTitulo: false },
);
</script>

<template>
    <section
        class="rv min-w-0 overflow-hidden rounded-[22px] border bg-card"
        :style="{ '--i': indice, '--fila-py': relleno }"
    >
        <header
            :class="
                ocultarTitulo
                    ? 'sr-only'
                    : 'flex min-h-[66px] flex-wrap items-center gap-x-3 gap-y-1 border-b border-line-faint px-6 py-5'
            "
        >
            <h2 class="font-heading text-lg leading-[normal] font-bold">
                {{ titulo }}
            </h2>
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
    border-collapse: collapse;
    font-size: 14px;
    text-align: left;
}
.tabla-pos :deep(thead) {
    line-height: normal;
    background: var(--background);
    color: var(--muted-foreground);
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
.tabla-pos :deep(th),
.tabla-pos :deep(td) {
    padding: var(--fila-py, 14px) 16px;
}
.tabla-pos :deep(th) {
    font-weight: 700;
    white-space: nowrap;
}
.tabla-pos :deep(th) {
    padding-block: 14px;
}
.tabla-pos :deep(th:first-child),
.tabla-pos :deep(td:first-child),
.tabla-pos :deep(th:last-child),
.tabla-pos :deep(td:last-child) {
    padding-inline: 24px;
}
.tabla-pos :deep(tbody tr) {
    border-top: 1px solid var(--line-faint);
    transition: background 0.2s;
}
.tabla-pos :deep(tbody tr:hover) {
    background: var(--hover-row);
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
@media (prefers-reduced-motion: reduce) {
    .tabla-pos :deep(tbody tr) {
        transition: none;
    }
}
</style>
