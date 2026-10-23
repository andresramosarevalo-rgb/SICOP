<script setup lang="ts">
import { etiquetaCampo, formatearValorAuditoria } from '@/lib/formato';
import type { Auditoria } from '@/types';

defineProps<{
    auditoria: Auditoria;
}>();
</script>

<template>
    <ul class="grid gap-1 text-sm">
        <li v-for="(cambio, campo) in auditoria.cambios" :key="campo">
            <span class="text-muted-foreground"
                >{{ etiquetaCampo(String(campo)) }}:</span
            >
            <template v-if="auditoria.evento === 'modificado'">
                {{ formatearValorAuditoria(String(campo), cambio.antes) }}
                →
                <span class="font-medium">{{
                    formatearValorAuditoria(String(campo), cambio.despues)
                }}</span>
            </template>
            <template v-else>
                {{
                    formatearValorAuditoria(
                        String(campo),
                        auditoria.evento === 'creado'
                            ? cambio.despues
                            : cambio.antes,
                    )
                }}
            </template>
        </li>
    </ul>
</template>
