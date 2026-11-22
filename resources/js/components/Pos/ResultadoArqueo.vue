<script setup lang="ts">
import { formatearPesos } from '@/lib/formato';
import type { CierreCaja } from '@/types';

defineProps<{
    cierre: CierreCaja;
}>();
</script>

<template>
    <section
        class="flex flex-col gap-5 rounded-xl border bg-card p-5 shadow-sm sm:p-6"
    >
        <h2 class="text-base font-semibold">Cierre del día</h2>
        <dl class="grid gap-3">
            <div
                class="rounded-lg border border-brand-gold/30 bg-accent/50 p-4"
            >
                <dt class="text-sm text-muted-foreground">A consignar</dt>
                <dd class="text-xl font-semibold">
                    {{ formatearPesos(cierre.valor_a_consignar) }}
                </dd>
            </div>
            <div v-if="cierre.justificacion">
                <dt class="text-sm text-muted-foreground">Justificación</dt>
                <dd class="whitespace-pre-line">{{ cierre.justificacion }}</dd>
            </div>
        </dl>
        <p v-if="cierre.cerrado_por" class="text-xs text-muted-foreground">
            Cerrado por {{ cierre.cerrado_por.name }}
        </p>
    </section>
</template>
