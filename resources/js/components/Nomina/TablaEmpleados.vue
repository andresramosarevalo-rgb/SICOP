<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import nomina from '@/routes/nomina';
import type { Empleado } from '@/types';

defineProps<{
    empleados: Empleado[];
}>();
</script>

<template>
    <table class="min-w-[1000px]">
        <thead>
            <tr>
                <th scope="col">Documento</th>
                <th scope="col">Nombre</th>
                <th scope="col">Sede</th>
                <th scope="col">Área</th>
                <th scope="col">Cargo</th>
                <th scope="col">Estado</th>
                <th scope="col">Contrato</th>
                <th scope="col"><span class="sr-only">Acciones</span></th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="empleado in empleados" :key="empleado.id">
                <td class="whitespace-nowrap">
                    {{ empleado.tipo_documento }}
                    {{ empleado.numero_documento }}
                </td>
                <td class="font-semibold">
                    {{ empleado.nombres }} {{ empleado.apellidos }}
                </td>
                <td>{{ empleado.sede?.nombre ?? '—' }}</td>
                <td>{{ empleado.area?.nombre }}</td>
                <td>{{ empleado.cargo }}</td>
                <td>
                    <span
                        class="inline-flex rounded-full px-3 py-[5px] text-[13px] leading-[normal] font-semibold"
                        :class="
                            empleado.es_activo
                                ? 'bg-ok-bg text-success'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        {{ empleado.es_activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                <td class="capitalize">
                    <span
                        v-if="!empleado.contrato_vigente"
                        class="inline-flex rounded-full bg-danger-bg px-3 py-[5px] text-[13px] leading-[normal] font-semibold text-danger normal-case"
                        >Sin contrato</span
                    >
                    <span v-else>{{
                        empleado.contrato_vigente.periodicidad_pago
                    }}</span>
                </td>
                <td>
                    <Button
                        variant="outline"
                        as-child
                        class="min-h-9 rounded-[10px] border-line-strong bg-transparent px-3.5 text-[13px] font-semibold shadow-none hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent"
                    >
                        <Link :href="nomina.empleados.show(empleado.id)"
                            >Ver expediente</Link
                        >
                    </Button>
                </td>
            </tr>
        </tbody>
    </table>
</template>
