<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
                    <th class="px-4 py-2 font-medium">Contrato</th>
                    <th class="px-4 py-2 font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="empleado in empleados" :key="empleado.id">
                    <td class="px-4 py-2 whitespace-nowrap">
                        {{ empleado.tipo_documento }}
                        {{ empleado.numero_documento }}
                    </td>
                    <td class="px-4 py-2 font-medium">
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
                    <td class="px-4 py-2 capitalize">
                        <Badge
                            v-if="!empleado.contrato_vigente"
                            variant="destructive"
                            >Sin contrato</Badge
                        >
                        <span v-else>{{
                            empleado.contrato_vigente.periodicidad_pago
                        }}</span>
                    </td>
                    <td class="px-4 py-2">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="nomina.empleados.show(empleado.id)"
                                >Ver expediente</Link
                            >
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
