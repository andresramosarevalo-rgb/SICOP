<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import nomina from '@/routes/nomina';
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
                        <Link
                            :href="nomina.empleados.show(empleado.id)"
                            class="font-medium underline-offset-4 hover:underline"
                        >
                            {{ empleado.apellidos }}, {{ empleado.nombres }}
                        </Link>
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
