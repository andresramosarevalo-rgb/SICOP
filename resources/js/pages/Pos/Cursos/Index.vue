<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import { Button } from '@/components/ui/button';
import { formatearPesos } from '@/lib/formato';
import pos from '@/routes/pos';
import type { Curso, Sede } from '@/types';

const props = defineProps<{
    sede: Sede;
    cursos: Curso[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Sedes', href: pos.sedes.index() },
            { title: 'Cursos', href: '' },
        ],
    },
});
</script>

<template>
    <Head :title="`Cursos · ${props.sede.nombre}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.sedes.index()" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Cursos"
                :description="`${props.sede.nombre} · ${props.sede.ciudad}`"
            />
            <Button v-if="props.sede.es_activa" as-child>
                <Link :href="pos.sedes.cursos.create(props.sede.id)"
                    >Registrar curso</Link
                >
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Fecha</th>
                        <th class="px-4 py-2 font-medium">Documento</th>
                        <th class="px-4 py-2 font-medium">Cliente</th>
                        <th class="px-4 py-2 font-medium">Comparendo</th>
                        <th class="px-4 py-2 font-medium">Valor</th>
                        <th class="px-4 py-2 font-medium">Descuento</th>
                        <th class="px-4 py-2">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="registro in cursos" :key="registro.id">
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ registro.fecha }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ registro.tipo_documento }}
                            {{ registro.numero_documento }}
                        </td>
                        <td class="px-4 py-2">
                            {{ registro.nombre_cliente }}
                        </td>
                        <td class="px-4 py-2">
                            {{ registro.tipo_comparendo }}
                        </td>
                        <td class="px-4 py-2">
                            {{ formatearPesos(registro.valor_curso) }}
                        </td>
                        <td class="px-4 py-2">
                            {{ Number(registro.porcentaje_descuento) }} %
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex justify-end">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="pos.cursos.edit(registro.id)"
                                        >Editar</Link
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="cursos.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Esta sede aún no ha registrado cursos.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
