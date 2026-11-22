<script setup lang="ts">
import { formatearFecha, formatearPesos } from '@/lib/formato';
import type { Contrato } from '@/types';

defineProps<{
    contratos: Contrato[];
}>();
</script>

<template>
    <table class="min-w-[720px]">
        <thead>
            <tr>
                <th scope="col">Tipo</th>
                <th scope="col">Periodicidad</th>
                <th scope="col">Vigencia</th>
                <th scope="col">Salario base</th>
                <th scope="col">Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="contrato in contratos" :key="contrato.id">
                <td>{{ contrato.tipo_contrato }}</td>
                <td class="capitalize">{{ contrato.periodicidad_pago }}</td>
                <td class="whitespace-nowrap">
                    {{ formatearFecha(contrato.fecha_inicio) }} –
                    {{
                        contrato.fecha_fin
                            ? formatearFecha(contrato.fecha_fin)
                            : 'sin fecha de fin'
                    }}
                </td>
                <td class="font-heading whitespace-nowrap">
                    {{ formatearPesos(contrato.valor_salario_base) }}
                </td>
                <td>
                    <span
                        class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                        :class="
                            contrato.es_vigente
                                ? 'bg-ok-bg text-success'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        {{ contrato.es_vigente ? 'Vigente' : 'Finalizado' }}
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
</template>
