<script setup lang="ts">
import { formatearPesos } from '@/lib/formato';
import type { CierreCaja } from '@/types';

defineProps<{
    cierre: CierreCaja;
}>();
</script>

<template>
    <section class="rv flex flex-col gap-3.5" style="--i: 4">
        <h2 class="font-heading text-lg leading-[normal] font-bold">
            Cierre del día
        </h2>
        <dl
            class="grid grid-cols-[repeat(auto-fit,minmax(min(200px,100%),1fr))] gap-4"
        >
            <div
                class="rounded-[18px] border border-brand-gold/55 bg-cream p-5"
            >
                <dt class="text-sm leading-[normal] text-muted-foreground">
                    A consignar
                </dt>
                <dd
                    class="mt-1.5 font-heading text-2xl leading-[normal] font-extrabold"
                >
                    {{ formatearPesos(cierre.valor_a_consignar) }}
                </dd>
            </div>
            <div
                v-if="cierre.justificacion"
                class="col-span-full rounded-[18px] border bg-card px-5 py-[18px] text-sm"
            >
                <dt class="inline text-muted-foreground">Justificación:</dt>
                <dd class="inline whitespace-pre-line">
                    {{ cierre.justificacion }}
                </dd>
            </div>
        </dl>
        <p v-if="cierre.cerrado_por" class="text-xs text-ink-3">
            Cerrado por {{ cierre.cerrado_por.name }}
        </p>
    </section>
</template>
