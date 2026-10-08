<script setup lang="ts">
import { formatearPesos } from '@/lib/formato';
import type { DetalleReciboNomina } from '@/types';

defineProps<{
    titulo: string;
    detalles: DetalleReciboNomina[];
    total: string;
}>();
</script>

<template>
    <table class="w-full text-left text-sm">
        <thead class="border-b text-muted-foreground">
            <tr>
                <th class="py-2 font-medium">{{ titulo }}</th>
                <th class="py-2 text-right font-medium">Cantidad</th>
                <th class="py-2 text-right font-medium">Valor</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <tr v-for="detalle in detalles" :key="detalle.id">
                <td class="py-2">{{ detalle.descripcion }}</td>
                <td class="py-2 text-right">{{ detalle.cantidad ?? '' }}</td>
                <td class="py-2 text-right">
                    {{ formatearPesos(detalle.valor_concepto) }}
                </td>
            </tr>
        </tbody>
        <tfoot class="border-t font-medium">
            <tr>
                <td class="py-2" colspan="2">
                    Total {{ titulo.toLowerCase() }}
                </td>
                <td class="py-2 text-right">{{ formatearPesos(total) }}</td>
            </tr>
        </tfoot>
    </table>
</template>
