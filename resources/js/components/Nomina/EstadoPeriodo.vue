<script setup lang="ts">
import type { PeriodoNomina } from '@/types';

defineProps<{
    estado: PeriodoNomina['estado'];
}>();

const etiquetas: Record<PeriodoNomina['estado'], string> = {
    borrador: 'Borrador',
    liquidado: 'Liquidado',
    cerrado: 'Cerrado',
    reabierto: 'Reabierto',
};

const clases: Record<PeriodoNomina['estado'], string> = {
    borrador: 'bg-muted text-muted-foreground',
    liquidado: 'bg-cream text-warning',
    cerrado: 'bg-ok-bg text-success',
    reabierto: 'bg-brand-gold/25 text-warning',
};
</script>

<template>
    <span
        class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
        :class="clases[estado]"
    >
        {{ etiquetas[estado] }}
    </span>
</template>
