<script setup lang="ts">
import { etiquetaCampo, formatearValorAuditoria } from '@/lib/formato';
import type { Auditoria } from '@/types';

defineProps<{
    auditoria: Auditoria;
}>();
</script>

<template>
    <ul class="grid gap-1.5 text-[13px]">
        <li
            v-for="(cambio, campo) in auditoria.cambios"
            :key="campo"
            class="flex flex-wrap items-center gap-1.5"
        >
            <span class="text-muted-foreground"
                >{{ etiquetaCampo(String(campo)) }}:</span
            >
            <template v-if="auditoria.evento === 'modificado'">
                <span
                    class="rounded-lg bg-danger-bg px-2 py-0.5 text-danger line-through"
                    >{{
                        formatearValorAuditoria(String(campo), cambio.antes)
                    }}</span
                >
                <span class="rounded-lg bg-ok-bg px-2 py-0.5 text-success">{{
                    formatearValorAuditoria(String(campo), cambio.despues)
                }}</span>
            </template>
            <template v-else>
                <span
                    class="rounded-lg px-2 py-0.5"
                    :class="
                        auditoria.evento === 'creado'
                            ? 'bg-ok-bg text-success'
                            : 'bg-danger-bg text-danger'
                    "
                    >{{
                        formatearValorAuditoria(
                            String(campo),
                            auditoria.evento === 'creado'
                                ? cambio.despues
                                : cambio.antes,
                        )
                    }}</span
                >
            </template>
        </li>
    </ul>
</template>
