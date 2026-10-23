<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import BotonAtras from '@/components/Nomina/BotonAtras.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { etiquetaFormaPago, formatearPesos } from '@/lib/formato';
import pos from '@/routes/pos';
import type { Curso } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Punto de venta', href: pos.inicio.index() },
            { title: 'Cursos de todas las sedes', href: '' },
        ],
    },
});

defineProps<{
    fecha: string;
    cursos: Curso[];
    valorTotal: number;
}>();

function filtrarPorDia(fecha: string | number): void {
    if (fecha) {
        router.get(pos.cursos.index(), { fecha }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Cursos de todas las sedes" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <BotonAtras :respaldo="pos.inicio.index()" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Cursos de todas las sedes"
                description="Cursos registrados por las sedes en el día seleccionado."
            />
            <div class="grid gap-2">
                <Label for="fecha">Día</Label>
                <Input
                    id="fecha"
                    type="date"
                    :model-value="fecha"
                    @update:model-value="filtrarPorDia"
                />
            </div>
        </div>

        <p class="text-sm text-muted-foreground">
            {{ cursos.length }}
            {{ cursos.length === 1 ? 'curso' : 'cursos' }} ·
            {{ formatearPesos(valorTotal) }}
        </p>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Sede</th>
                        <th class="px-4 py-2 font-medium">Documento</th>
                        <th class="px-4 py-2 font-medium">Cliente</th>
                        <th class="px-4 py-2 font-medium">Comparendo</th>
                        <th class="px-4 py-2 font-medium">N.º comparendo</th>
                        <th class="px-4 py-2 font-medium">Pago</th>
                        <th class="px-4 py-2 font-medium">Valor</th>
                        <th class="px-4 py-2 font-medium">Descuento</th>
                        <th class="px-4 py-2">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="curso in cursos" :key="curso.id">
                        <td class="px-4 py-2">
                            {{ curso.sede?.nombre }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ curso.tipo_documento }}
                            {{ curso.numero_documento }}
                        </td>
                        <td class="px-4 py-2">{{ curso.nombre_cliente }}</td>
                        <td class="px-4 py-2">{{ curso.tipo_comparendo }}</td>
                        <td class="px-4 py-2">{{ curso.numero_comparendo }}</td>
                        <td class="px-4 py-2">
                            {{ etiquetaFormaPago(curso.forma_pago) }}
                        </td>
                        <td class="px-4 py-2">
                            {{ formatearPesos(curso.valor_curso) }}
                        </td>
                        <td class="px-4 py-2">
                            {{ Number(curso.porcentaje_descuento) }} %
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex justify-end">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="pos.cursos.edit(curso.id)"
                                        >Editar</Link
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="cursos.length === 0">
                        <td
                            colspan="9"
                            class="px-4 py-6 text-center text-muted-foreground"
                        >
                            Ninguna sede registró cursos este día.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
