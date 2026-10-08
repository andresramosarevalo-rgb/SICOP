<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { formatearPesos } from '@/lib/formato';
import type { Contrato } from '@/types';

defineProps<{
    contratos: Contrato[];
}>();
</script>

<template>
    <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-muted/50 text-muted-foreground">
                <tr>
                    <th class="px-4 py-2 font-medium">Tipo</th>
                    <th class="px-4 py-2 font-medium">Periodicidad</th>
                    <th class="px-4 py-2 font-medium">Vigencia</th>
                    <th class="px-4 py-2 text-right font-medium">
                        Salario base
                    </th>
                    <th class="px-4 py-2 font-medium">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="contrato in contratos" :key="contrato.id">
                    <td class="px-4 py-2">{{ contrato.tipo_contrato }}</td>
                    <td class="px-4 py-2">{{ contrato.periodicidad_pago }}</td>
                    <td class="px-4 py-2 whitespace-nowrap">
                        {{ contrato.fecha_inicio }} —
                        {{ contrato.fecha_fin ?? 'sin fecha de fin' }}
                    </td>
                    <td class="px-4 py-2 text-right">
                        {{ formatearPesos(contrato.valor_salario_base) }}
                    </td>
                    <td class="px-4 py-2">
                        <Badge
                            :variant="
                                contrato.es_vigente ? 'secondary' : 'outline'
                            "
                        >
                            {{ contrato.es_vigente ? 'Vigente' : 'Terminado' }}
                        </Badge>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
