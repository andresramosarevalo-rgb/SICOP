<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { PeriodoNomina } from '@/types';

defineProps<{
    estado: PeriodoNomina['estado'];
}>();

const etiquetas: Record<PeriodoNomina['estado'], string> = {
    borrador: 'Borrador',
    liquidado: 'Liquidado',
    cerrado: 'Cerrado',
};
</script>

<template>
    <Badge
        :variant="
            estado === 'cerrado'
                ? 'default'
                : estado === 'liquidado'
                  ? 'secondary'
                  : 'outline'
        "
    >
        {{ etiquetas[estado] }}
    </Badge>
</template>
