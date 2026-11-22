<script setup lang="ts">
import { formatearPesos } from '@/lib/formato';
import type { DetalleReciboNomina } from '@/types';

defineProps<{
    titulo: string;
    detalles: DetalleReciboNomina[];
    indice: number;
}>();
</script>

<template>
    <section
        class="rv min-w-0 flex-[1_1_380px] rounded-[22px] border bg-card px-7 py-6 print:break-inside-avoid print:border"
        :style="{ '--i': indice }"
    >
        <h2 class="mb-3 font-heading text-lg leading-[normal] font-bold">
            {{ titulo }}
        </h2>
        <table class="w-full border-collapse text-left text-sm">
            <thead>
                <tr class="border-b text-muted-foreground">
                    <th scope="col" class="py-2.5 font-semibold">Concepto</th>
                    <th scope="col" class="py-2.5 text-right font-semibold">
                        Cantidad
                    </th>
                    <th scope="col" class="py-2.5 text-right font-semibold">
                        Valor
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="detalle in detalles"
                    :key="detalle.id"
                    class="border-t border-line-faint first:border-t-0"
                >
                    <td class="py-3">{{ detalle.descripcion }}</td>
                    <td class="py-3 text-right">
                        {{ detalle.cantidad ?? '—' }}
                    </td>
                    <td class="py-3 text-right font-heading">
                        {{ formatearPesos(detalle.valor_concepto) }}
                    </td>
                </tr>
                <tr v-if="detalles.length === 0">
                    <td colspan="3" class="py-3 text-muted-foreground">
                        Sin conceptos.
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>
