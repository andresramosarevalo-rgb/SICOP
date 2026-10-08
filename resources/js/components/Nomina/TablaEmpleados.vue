<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { Empleado } from '@/types';

defineProps<{
    empleados: Empleado[];
}>();
</script>

<template>
    <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-muted/50 text-muted-foreground">
                <tr>
                    <th class="px-4 py-2 font-medium">Documento</th>
                    <th class="px-4 py-2 font-medium">Nombre</th>
                    <th class="px-4 py-2 font-medium">Área</th>
                    <th class="px-4 py-2 font-medium">Cargo</th>
                    <th class="px-4 py-2 font-medium">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="empleado in empleados" :key="empleado.id">
                    <td class="px-4 py-2 whitespace-nowrap">
                        {{ empleado.tipo_documento }}
                        {{ empleado.numero_documento }}
                    </td>
                    <td class="px-4 py-2">
                        {{ empleado.apellidos }}, {{ empleado.nombres }}
                    </td>
                    <td class="px-4 py-2">{{ empleado.area?.nombre }}</td>
                    <td class="px-4 py-2">{{ empleado.cargo }}</td>
                    <td class="px-4 py-2">
                        <Badge
                            :variant="
                                empleado.es_activo ? 'secondary' : 'outline'
                            "
                        >
                            {{ empleado.es_activo ? 'Activo' : 'Inactivo' }}
                        </Badge>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
